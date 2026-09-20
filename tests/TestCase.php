<?php

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Tests\Support\MeasurementsMetricProvider;
use Tests\Support\User;
use Whilesmart\Admin\AdminServiceProvider;
use Whilesmart\Engagement\EngagementServiceProvider;
use Whilesmart\OwnerAccess\OwnerAccessServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email');
            $table->timestamps();
        });
    }

    protected function getPackageProviders($app): array
    {
        return [OwnerAccessServiceProvider::class, EngagementServiceProvider::class, AdminServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('admin.route_middleware', ['api']);
        $app['config']->set('admin.user_model', User::class);
        $app['config']->set('engagement.providers', [MeasurementsMetricProvider::class]);
        $app['config']->set('engagement.clients', [
            'website' => [
                'name' => 'Website',
                'site_key' => 'website-key',
                'allowed_origins' => ['https://example.com'],
            ],
        ]);
        $app['config']->set('admin.templates', [
            'welcome' => [
                'name' => 'Welcome email',
                'description' => 'Sent after registration.',
                'subject' => 'Welcome {{first_name}}',
                'body' => 'Hello {{name}}.',
                'cta_label' => 'Open',
                'cta_url' => 'https://example.com',
            ],
        ]);
    }
}
