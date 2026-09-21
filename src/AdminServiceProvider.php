<?php

namespace Whilesmart\Admin;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Whilesmart\Admin\Contracts\AdminUserProvider;
use Whilesmart\Admin\Listeners\SendRegistrationEmail;
use Whilesmart\Admin\Support\OfferRegistry;
use Whilesmart\UserAuthentication\Events\UserRegisteredEvent;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/admin.php', 'admin');
        $this->app->bind(AdminUserProvider::class, function ($app) {
            $provider = config('admin.user_provider');
            if (! is_string($provider) || ! is_subclass_of($provider, AdminUserProvider::class)) {
                throw new InvalidArgumentException('admin.user_provider must implement AdminUserProvider.');
            }

            return $app->make($provider);
        });

        $this->app->singleton(
            OfferRegistry::class,
            fn () => new OfferRegistry((array) config('admin.offer_providers', [])),
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'admin');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Event::listen(UserRegisteredEvent::class, SendRegistrationEmail::class);
        $this->publishes([__DIR__.'/../config/admin.php' => config_path('admin.php')], 'admin-config');
        $this->publishes([__DIR__.'/../database/migrations' => database_path('migrations')], 'admin-migrations');

        if (config('admin.register_routes', true)) {
            Route::middleware(config('admin.route_middleware', ['api', 'auth:sanctum']))
                ->prefix(config('admin.route_prefix', 'api/admin'))
                ->group(__DIR__.'/../routes/api.php');
        }
    }
}
