<?php

namespace Tests\Feature;

use Tests\Support\User;
use Tests\TestCase;
use Whilesmart\Admin\Offers\FreePasses;
use Whilesmart\Entitlements\EntitlementsServiceProvider;
use Whilesmart\Entitlements\Models\Plan;
use Whilesmart\Entitlements\Models\Subscription;

class FreePassesTest extends TestCase
{
    private User $admin;

    private User $person;

    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), EntitlementsServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('entitlements.route_middleware', ['api']);
        $app['config']->set('admin.offer_providers', [FreePasses::class]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        Plan::factory()->create(['key' => 'free', 'name' => 'Free']);
        Plan::factory()->create(['key' => 'starter', 'name' => 'Starter', 'features' => ['reviews' => true]]);
        $this->admin = User::create(['email' => 'owner@example.test']);
        $this->person = User::create(['email' => 'ada@example.test']);
    }

    private function give(array $attributes = [])
    {
        return $this->actingAs($this->admin)->postJson('api/admin/offers/free-passes', ['attributes' => [
            'person' => 'ada@example.test',
            'plan' => 'starter',
            ...$attributes,
        ]]);
    }

    private function plan()
    {
        return $this->getJson('/api/entitlements?'.http_build_query([
            'owner_type' => $this->person->getMorphClass(),
            'owner_id' => $this->person->getKey(),
        ]))->json('data.plan.key');
    }

    public function test_the_console_heads_passes_by_person_and_plan(): void
    {
        $this->actingAs($this->admin)
            ->getJson('api/admin/offers')
            ->assertOk()
            ->assertJsonPath('data.0.label', 'Free passes')
            ->assertJsonPath('data.0.columns', ['code' => 'Person', 'value' => 'Plan', 'until' => 'Until'])
            ->assertJsonPath('data.0.fields.1.options', ['free' => 'Free', 'starter' => 'Starter']);
    }

    public function test_a_pass_gives_the_person_the_plan_until_its_end_date(): void
    {
        $this->give(['until' => now()->addDays(30)->toDateString()])
            ->assertCreated()
            ->assertJsonPath('data.code', 'ada@example.test')
            ->assertJsonPath('data.value', 'Starter')
            ->assertJsonPath('data.active', true);
        $this->assertSame('starter', $this->plan());

        $this->travel(31)->days();

        $this->assertNull($this->plan());
        $this->actingAs($this->admin)->getJson('api/admin/offers')->assertJsonPath('data.0.offers.0.active', false);
    }

    public function test_revoking_a_pass_takes_the_plan_away_at_once(): void
    {
        $id = $this->give()->json('data.id');

        $this->actingAs($this->admin)->deleteJson("api/admin/offers/free-passes/{$id}")->assertSuccessful();

        $this->assertNull($this->plan());
        $this->actingAs($this->admin)->deleteJson("api/admin/offers/free-passes/{$id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'No running free pass has that id.');
    }

    public function test_a_person_with_a_live_plan_is_not_given_a_pass(): void
    {
        Subscription::factory()->create([
            'owner_type' => $this->person->getMorphClass(),
            'owner_id' => $this->person->getKey(),
            'plan_id' => Plan::where('key', 'free')->value('id'),
        ]);

        $this->give()->assertStatus(422)->assertJsonPath('message', 'They already have a plan.');
    }

    public function test_a_pass_needs_somebody_who_is_here(): void
    {
        $this->give(['person' => 'nobody@example.test'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Nobody here uses the address nobody@example.test.');
    }
}
