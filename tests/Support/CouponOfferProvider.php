<?php

namespace Tests\Support;

use Illuminate\Support\Carbon;
use Whilesmart\Admin\Contracts\OfferField;
use Whilesmart\Admin\Contracts\OfferProvider;
use Whilesmart\Admin\Support\Offer;

/**
 * A provider backed by an array, standing in for whatever a host really keeps
 * its discounts in.
 */
class CouponOfferProvider implements OfferProvider
{
    /** @var array<string, Offer> */
    public static array $offers = [];

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

    public function all(): array
    {
        return array_values(static::$offers);
    }

    public function create(array $attributes): Offer
    {
        $offer = new Offer(
            id: (string) (count(static::$offers) + 1),
            code: $attributes['code'],
            value: $attributes['percent_off'].'%',
            expiresAt: isset($attributes['expires_at']) ? Carbon::parse($attributes['expires_at']) : null,
        );

        static::$offers[$offer->id] = $offer;

        return $offer;
    }

    public function revoke(string $id): void
    {
        unset(static::$offers[$id]);
    }
}
