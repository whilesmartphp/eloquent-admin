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

## Offers

A discount is stored differently in every product, so the console asks a
provider rather than a table. Register one and the discounts page lists what it
holds and renders a form from the fields it declares.

```php
return [
    'offer_providers' => [
        App\Billing\CouponOfferProvider::class,
    ],
];
```

```php
use Whilesmart\Admin\Contracts\OfferField;
use Whilesmart\Admin\Contracts\OfferProvider;
use Whilesmart\Admin\Support\Offer;

class CouponOfferProvider implements OfferProvider
{
    public function key(): string
    {
        return 'coupons';
    }

    public function label(): string
    {
        return 'Coupons';
    }

    public function fields(): array
    {
        return [
            new OfferField('code', 'Code', required: true),
            new OfferField('percent_off', 'Percent off', 'number', required: true),
            new OfferField('expires_at', 'Expires', 'date'),
        ];
    }

    public function all(): array { /* Offer[] */ }

    public function create(array $attributes): Offer { /* ... */ }

    public function revoke(string $id): void { /* ... */ }
}
```

`Offer::$value` is already formatted, because only the provider knows whether
20 means a percentage, pennies or seats. Revoking stops an offer being redeemed
again and leaves redemptions already made standing.

Registering no provider is a supported state: the endpoint answers with an
empty list and the page says so.
