<?php

namespace Whilesmart\Admin\Support;

use DateTimeInterface;

/**
 * One discount, as the console shows it. A provider keeps whatever else its
 * own storage holds.
 */
final class Offer
{
    /**
     * @param  string  $value  Already formatted, because only the provider knows
     *                         whether 20 means a percentage, pennies, or seats.
     * @param  DateTimeInterface|null  $expiresAt  Whatever date the provider holds.
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $id,
        public readonly string $code,
        public readonly string $value,
        public readonly ?DateTimeInterface $expiresAt = null,
        public readonly ?int $redemptions = null,
        public readonly ?int $maxRedemptions = null,
        public readonly bool $active = true,
        public readonly array $meta = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'value' => $this->value,
            'expires_at' => $this->expiresAt?->format(DateTimeInterface::ATOM),
            'redemptions' => $this->redemptions,
            'max_redemptions' => $this->maxRedemptions,
            'active' => $this->active,
            'meta' => $this->meta,
        ];
    }
}
