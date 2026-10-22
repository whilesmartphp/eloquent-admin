<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\User;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    private function actor(): User
    {
        return User::create(['first_name' => 'Ada', 'email' => 'ada@example.test']);
    }

    #[Test]
    public function the_owner_is_given_the_shared_dashboard(): void
    {
        config(['admin.analytics.dashboard_url' => 'https://us.posthog.com/embedded/abc123']);

        $this->actingAs($this->actor())
            ->getJson('/api/admin/analytics')
            ->assertOk()
            ->assertJsonPath('data.dashboard_url', 'https://us.posthog.com/embedded/abc123');
    }

    #[Test]
    public function no_dashboard_is_given_when_none_is_configured(): void
    {
        $this->actingAs($this->actor())
            ->getJson('/api/admin/analytics')
            ->assertOk()
            ->assertJsonPath('data.dashboard_url', null);
    }

    #[Test]
    #[DataProvider('unsafeAddresses')]
    public function an_address_that_is_not_https_is_never_given(string $url): void
    {
        config(['admin.analytics.dashboard_url' => $url]);

        $this->actingAs($this->actor())
            ->getJson('/api/admin/analytics')
            ->assertOk()
            ->assertJsonPath('data.dashboard_url', null);
    }

    public static function unsafeAddresses(): array
    {
        return [
            'insecure' => ['http://us.posthog.com/embedded/abc123'],
            'script' => ['javascript:alert(1)'],
            'not an address' => ['dashboard'],
        ];
    }
}
