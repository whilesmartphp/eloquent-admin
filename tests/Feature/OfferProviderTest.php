<?php

namespace Tests\Feature;

use Carbon\Carbon as BaseCarbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon as LaravelCarbon;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CouponOfferProvider;
use Tests\Support\SecondCouponProvider;
use Tests\Support\StrictCreateOfferRequest;
use Tests\Support\User;
use Tests\TestCase;
use Whilesmart\Admin\Contracts\OfferField;
use Whilesmart\Admin\Http\Requests\UpdateMailTemplateRequest;
use Whilesmart\Admin\Support\Offer;
use Whilesmart\Admin\Support\OfferRegistry;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

/**
 * What the console can do with a host's discounts without knowing what backs
 * them.
 */
class OfferProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CouponOfferProvider::$offers = [];
        config(['admin.offer_providers' => [CouponOfferProvider::class]]);
    }

    private function actor(): User
    {
        return User::create(['first_name' => 'Ada', 'email' => 'ada@example.test']);
    }

    public function test_a_registered_provider_says_what_it_can_be_asked_for(): void
    {
        $this->actingAs($this->actor())
            ->getJson('api/admin/offers')
            ->assertOk()
            ->assertJsonPath('data.0.key', 'coupons')
            ->assertJsonPath('data.0.label', 'Coupons')
            ->assertJsonPath('data.0.fields.0.name', 'code')
            ->assertJsonPath('data.0.fields.0.required', true)
            ->assertJsonPath('data.0.fields.1.type', 'number')
            ->assertJsonPath('data.0.offers', []);
    }

    public function test_a_host_with_no_provider_gets_an_empty_list_rather_than_an_error(): void
    {
        config(['admin.offer_providers' => []]);

        $this->actingAs($this->actor())
            ->getJson('api/admin/offers')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_an_offer_can_be_created_and_is_then_listed(): void
    {
        $this->actingAs($this->actor())
            ->postJson('api/admin/offers/coupons', ['attributes' => [
                'code' => 'PILOT20',
                'percent_off' => 20,
            ]])
            ->assertCreated()
            ->assertJsonPath('data.code', 'PILOT20')
            ->assertJsonPath('data.value', '20%');

        $this->actingAs($this->actor())
            ->getJson('api/admin/offers')
            ->assertOk()
            ->assertJsonPath('data.0.offers.0.code', 'PILOT20');
    }

    public function test_creating_one_needs_attributes(): void
    {
        $this->actingAs($this->actor())
            ->postJson('api/admin/offers/coupons', [])
            ->assertStatus(422);
    }

    public function test_a_revoked_offer_stops_being_listed(): void
    {
        $created = $this->actingAs($this->actor())
            ->postJson('api/admin/offers/coupons', ['attributes' => ['code' => 'GONE', 'percent_off' => 5]])
            ->json('data.id');

        $this->actingAs($this->actor())
            ->deleteJson("api/admin/offers/coupons/{$created}")
            ->assertOk();

        $this->actingAs($this->actor())
            ->getJson('api/admin/offers')
            ->assertOk()
            ->assertJsonPath('data.0.offers', []);
    }

    #[DataProvider('expiryDates')]
    public function test_any_date_a_host_holds_can_be_an_expiry(callable $make): void
    {
        $offer = new Offer(id: '1', code: 'PILOT20', value: '20%', expiresAt: $make());

        $this->assertSame('2026-01-01T00:00:00+00:00', $offer->toArray()['expires_at']);
    }

    public static function expiryDates(): array
    {
        $moment = '2026-01-01T00:00:00+00:00';

        return [
            'carbon' => [fn () => BaseCarbon::parse($moment)],
            'carbon immutable' => [fn () => CarbonImmutable::parse($moment)],
            'laravel carbon' => [fn () => LaravelCarbon::parse($moment)],
            'date time' => [fn () => new \DateTime($moment)],
            'date time immutable' => [fn () => new \DateTimeImmutable($moment)],
        ];
    }

    public function test_offers_answer_to_the_configured_owner(): void
    {
        $this->app->instance(OwnerAuthorizer::class, new class implements OwnerAuthorizer
        {
            public function authorize(?Authenticatable $user, string $ownerType, mixed $ownerId): bool
            {
                return false;
            }

            public function scope(Builder $query, ?Authenticatable $user, string $ownerTypeColumn = 'owner_type', string $ownerIdColumn = 'owner_id'): Builder
            {
                return $query->whereRaw('0 = 1');
            }
        });

        $this->actingAs($this->actor())->getJson('api/admin/offers')->assertForbidden();
        $this->actingAs($this->actor())
            ->postJson('api/admin/offers/coupons', ['attributes' => ['code' => 'X']])
            ->assertForbidden();
        $this->actingAs($this->actor())
            ->deleteJson('api/admin/offers/coupons/1')
            ->assertForbidden();
    }

    public function test_a_host_upgrading_without_the_new_config_key_can_still_create(): void
    {
        config(['admin.requests' => ['update_mail_template' => UpdateMailTemplateRequest::class]]);

        $this->actingAs($this->actor())
            ->postJson('api/admin/offers/coupons', ['attributes' => [
                'code' => 'PILOT20',
                'percent_off' => 20,
            ]])
            ->assertCreated();
    }

    public function test_a_provider_that_does_not_implement_the_contract_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OfferRegistry([User::class]);
    }

    public function test_two_providers_under_one_key_are_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OfferRegistry([CouponOfferProvider::class, SecondCouponProvider::class]);
    }

    public function test_a_host_can_replace_the_request_that_validates_an_offer(): void
    {
        config(['admin.requests.create_offer' => StrictCreateOfferRequest::class]);

        $this->actingAs($this->actor())
            ->postJson('api/admin/offers/coupons', ['attributes' => [
                'code' => 'PILOT20',
                'percent_off' => 20,
            ]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('attributes.reason');
    }

    #[DataProvider('unknownProvider')]
    public function test_an_unregistered_provider_is_not_found(string $method, string $path): void
    {
        $this->actingAs($this->actor())
            ->json($method, $path, ['attributes' => ['code' => 'X']])
            ->assertNotFound();
    }

    public static function unknownProvider(): array
    {
        return [
            'creating' => ['POST', 'api/admin/offers/credits'],
            'revoking' => ['DELETE', 'api/admin/offers/credits/1'],
        ];
    }

    public function test_a_field_may_ask_for_a_person(): void
    {
        $field = new OfferField(
            name: 'for_email',
            label: 'Only for',
            type: 'user',
        );

        $this->assertSame('user', $field->toArray()['type']);
    }
}
