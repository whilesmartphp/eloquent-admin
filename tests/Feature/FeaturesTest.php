<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\User;
use Tests\TestCase;

class FeaturesTest extends TestCase
{
    private function actor(): User
    {
        return User::create(['first_name' => 'Ada', 'email' => 'ada@example.test']);
    }

    #[Test]
    public function an_optional_page_is_off_until_its_backend_is_there(): void
    {
        $this->actingAs($this->actor())
            ->getJson('/api/admin/features')
            ->assertOk()
            ->assertJsonPath('data.feedback', false)
            ->assertJsonPath('data.posthog', false);
    }

    #[Test]
    public function feedback_is_on_once_its_routes_are_served_under_the_console(): void
    {
        Route::middleware('api')->get('api/admin/feedback', fn () => []);

        $this->actingAs($this->actor())
            ->getJson('/api/admin/features')
            ->assertOk()
            ->assertJsonPath('data.feedback', true);
    }

    #[Test]
    public function posthog_is_on_once_a_shared_dashboard_is_set(): void
    {
        config(['admin.posthog.dashboard_url' => 'https://us.posthog.com/embedded/abc123']);

        $this->actingAs($this->actor())
            ->getJson('/api/admin/features')
            ->assertOk()
            ->assertJsonPath('data.posthog', true);
    }

    #[Test]
    public function posthog_stays_off_for_an_address_that_is_not_a_shared_dashboard(): void
    {
        config(['admin.posthog.dashboard_url' => 'https://us.posthog.com/project/1/dashboard/2']);

        $this->actingAs($this->actor())
            ->getJson('/api/admin/features')
            ->assertOk()
            ->assertJsonPath('data.posthog', false);
    }
}
