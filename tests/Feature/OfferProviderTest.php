<?php

namespace Tests\Feature;

use Tests\Support\CouponOfferProvider;
use Tests\Support\User;
use Tests\TestCase;

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

    /**
     * @dataProvider unknownProvider
     */
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
}
