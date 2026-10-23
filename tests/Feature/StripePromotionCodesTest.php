<?php

namespace Tests\Feature;

use Laravel\Cashier\CashierServiceProvider;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;
use Tests\Support\User;
use Tests\TestCase;
use Whilesmart\Admin\Offers\StripePromotionCodes;
use Whilesmart\Entitlements\EntitlementsServiceProvider;
use Whilesmart\EntitlementsCashier\EntitlementsCashierServiceProvider;
use Whilesmart\EntitlementsCashier\Models\BillingProfile;

class StripeTransport implements ClientInterface
{
    /** @var array<int, array{method: string, path: string, params: array}> */
    public array $requests = [];

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $path = (string) parse_url($absUrl, PHP_URL_PATH);
        $this->requests[] = ['method' => $method, 'path' => $path, 'params' => $params];
        $codes = json_decode(file_get_contents(__DIR__.'/../Fixtures/stripe-promotion-codes.json'), true);

        return match (true) {
            $method === 'get' && $path === '/v1/promotion_codes' => [json_encode($codes), 200, []],
            $method === 'post' && $path === '/v1/coupons' => [file_get_contents(__DIR__.'/../Fixtures/stripe-coupon.json'), 200, []],
            $method === 'post' && str_starts_with($path, '/v1/promotion_codes') => [json_encode($codes['data'][0]), 200, []],
        };
    }
}

class StripePromotionCodesTest extends TestCase
{
    private StripeTransport $transport;

    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), CashierServiceProvider::class, EntitlementsServiceProvider::class, EntitlementsCashierServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('cashier.secret', 'your-api-key-here');
        $app['config']->set('admin.offer_providers', [StripePromotionCodes::class]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->transport = new StripeTransport;
        ApiRequestor::setHttpClient($this->transport);
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(CurlClient::instance());
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::create(['email' => 'owner@example.test']);
    }

    public function test_a_code_whose_coupon_was_deleted_in_stripe_lists_as_stopped(): void
    {
        $this->actingAs($this->admin())
            ->getJson('api/admin/offers')
            ->assertOk()
            ->assertJsonPath('data.0.columns', null)
            ->assertJsonCount(5, 'data.0.offers')
            ->assertJsonPath('data.0.offers.0.code', 'JUSTFORFON')
            ->assertJsonPath('data.0.offers.0.value', 'Deleted in Stripe')
            ->assertJsonPath('data.0.offers.0.active', false);
    }

    public function test_a_code_held_for_one_person_is_made_against_their_stripe_customer(): void
    {
        $person = User::create(['email' => 'ada@example.test']);
        BillingProfile::forOwner($person)->update(['stripe_id' => 'cus_ada']);

        $this->actingAs($this->admin())
            ->postJson('api/admin/offers/stripe', ['attributes' => [
                'code' => 'PILOT3FREE',
                'percent_off' => '100',
                'duration' => 'repeating',
                'months' => '3',
                'for_email' => 'ada@example.test',
            ]])
            ->assertCreated()
            ->assertJsonPath('data.value', '50% off')
            ->assertJsonPath('data.meta.lasts', 'one bill');

        [$coupon, $promotion] = $this->transport->requests;
        $this->assertSame(['duration' => 'repeating', 'name' => 'PILOT3FREE', 'percent_off' => 100.0, 'duration_in_months' => 3], $coupon['params']);
        $this->assertSame('cus_ada', $promotion['params']['customer']);
    }

    public function test_a_refused_code_answers_with_why(): void
    {
        User::create(['email' => 'ada@example.test']);

        $this->actingAs($this->admin())
            ->postJson('api/admin/offers/stripe', ['attributes' => [
                'code' => 'PILOT20',
                'percent_off' => '20',
                'duration' => 'once',
                'for_email' => 'ada@example.test',
            ]])
            ->assertStatus(422)
            ->assertJsonPath('message', 'They have no billing account yet, so a code cannot be held for them.');
    }
}
