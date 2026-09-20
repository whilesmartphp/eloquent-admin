# whilesmart/eloquent-admin

Shared administration APIs for Laravel applications. It provides measurements, a user directory, and a registry-backed automatic email template system while leaving authorization, user fields, and product-specific sections to the host.

## Host configuration

```php
return [
    'route_prefix' => 'api/v1/admin',
    'route_middleware' => ['api', 'auth:sanctum', 'role:admin'],
    'user_model' => App\Models\User::class,
    'owner' => ['type' => 'platform', 'id' => 0],
    'templates' => [
        'welcome' => [
            'name' => 'Welcome email',
            'description' => 'Sent after registration.',
            'subject' => 'Welcome, {{first_name}}',
            'body' => 'Your account is ready.',
        ],
    ],
];
```

The host binds `OwnerAuthorizer` to protect the configured admin owner. Product-specific user fields belong in a custom API resource or user provider.

Measurements are registered through `whilesmart/eloquent-engagement`. Every registered metric provider is returned by the admin metrics endpoint and can be rendered by the companion Nuxt layer.

```php
use App\Engagement\OrdersMetricProvider;

return [
    'providers' => [
        OrdersMetricProvider::class,
    ],
];
```

Each provider implements `Whilesmart\Engagement\Contracts\MetricProvider` and returns count, sum, ratio, series, or ranking metrics. Adding a provider automatically adds its measurements to the shared admin page.
