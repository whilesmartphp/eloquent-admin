<?php

namespace Tests\Support;

/** A provider whose offers are not codes, so it names the columns itself. */
class PassOfferProvider extends CouponOfferProvider
{
    public function key(): string
    {
        return 'passes';
    }

    public function columns(): array
    {
        return ['code' => 'Person', 'value' => 'Plan', 'until' => 'Until'];
    }
}
