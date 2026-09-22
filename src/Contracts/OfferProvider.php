<?php

namespace Whilesmart\Admin\Contracts;

use Whilesmart\Admin\Support\Offer;

/**
 * Lists and creates a host's discounts without knowing what stores them.
 */
interface OfferProvider
{
    /** Stable machine key, unique across registered providers. */
    public function key(): string;

    /** Human label, shown as the section heading. */
    public function label(): string;

    /**
     * The inputs create() expects, which the console renders as a form.
     *
     * @return array<int, OfferField>
     */
    public function fields(): array;

    /**
     * @return Offer[]
     */
    public function all(): array;

    /**
     * @param  array<string, mixed>  $attributes  Keyed by the field names above.
     */
    public function create(array $attributes): Offer;

    /** Stop an offer being redeemed again. Redemptions already made stand. */
    public function revoke(string $id): void;
}
