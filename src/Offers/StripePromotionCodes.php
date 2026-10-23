<?php

namespace Whilesmart\Admin\Offers;

use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Whilesmart\Admin\Contracts\OfferField;
use Whilesmart\Admin\Contracts\OfferProvider;
use Whilesmart\Admin\Support\Offer;
use Whilesmart\EntitlementsCashier\Discounts\PromotionCode;
use Whilesmart\EntitlementsCashier\Discounts\PromotionCodes;

/**
 * Discount codes buyers type at checkout, from whilesmart/entitlements-cashier.
 *
 * Revoking a code ends it, including for subscriptions that already carry it, so
 * a code that lasts forever does not outlive its revoking.
 */
class StripePromotionCodes implements OfferProvider
{
    public function __construct(private readonly PromotionCodes $codes) {}

    public function key(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return 'Discounts';
    }

    public function fields(): array
    {
        return [
            new OfferField('code', 'Code', required: true, help: 'What the person types at checkout. PILOT20, say.'),
            new OfferField('percent_off', 'Percent off', 'number', help: 'Leave empty if taking an amount off instead.'),
            new OfferField('amount_off', 'Amount off', 'number', help: 'Off each bill. Leave empty if taking a percentage.'),
            new OfferField('duration', 'Lasts for', 'select', required: true, options: [
                PromotionCodes::ONCE => 'One bill',
                PromotionCodes::REPEATING => 'A number of months',
                PromotionCodes::FOREVER => 'Every bill',
            ]),
            new OfferField('months', 'How many months', 'number', help: 'Only when it lasts a number of months.'),
            new OfferField('max_redemptions', 'How many people may use it', 'number', help: 'Left empty, anybody with the code may.'),
            new OfferField('expires_at', 'Stops working after', 'date'),
            new OfferField('for_email', 'Only for', 'user', help: 'One person, who must have checked out before. Left empty, anybody may.'),
        ];
    }

    public function all(): array
    {
        return array_map(fn (PromotionCode $code) => $this->asOffer($code), $this->codes->all());
    }

    public function create(array $attributes): Offer
    {
        $for = null;
        if (filled($attributes['for_email'] ?? null)) {
            $users = config('admin.user_model');
            $for = $users::query()->where('email', (string) $attributes['for_email'])->first();
            abort_if($for === null, 422, "Nobody here uses the address {$attributes['for_email']}.");
        }

        try {
            $code = $this->codes->create(
                code: trim((string) ($attributes['code'] ?? '')),
                duration: (string) ($attributes['duration'] ?? ''),
                percentOff: $this->number($attributes['percent_off'] ?? null),
                amountOff: $this->number($attributes['amount_off'] ?? null),
                months: $this->whole($attributes['months'] ?? null),
                maxRedemptions: $this->whole($attributes['max_redemptions'] ?? null),
                expiresAt: filled($attributes['expires_at'] ?? null) ? Carbon::parse((string) $attributes['expires_at'])->endOfDay() : null,
                for: $for,
            );
        } catch (InvalidArgumentException $refused) {
            abort(422, $refused->getMessage());
        }

        return $this->asOffer($code);
    }

    public function revoke(string $id): void
    {
        $this->codes->end($id);
    }

    private function asOffer(PromotionCode $code): Offer
    {
        return new Offer(
            id: $code->id,
            code: $code->code,
            value: $this->takesOff($code),
            expiresAt: $code->expiresAt,
            redemptions: $code->redemptions,
            maxRedemptions: $code->maxRedemptions,
            active: $code->active,
            meta: ['lasts' => $this->lasts($code)],
        );
    }

    private function takesOff(PromotionCode $code): string
    {
        return match (true) {
            $code->couponDeleted => 'Deleted in Stripe',
            $code->percentOff !== null => rtrim(rtrim(number_format($code->percentOff, 2, '.', ''), '0'), '.').'% off',
            default => number_format(($code->amountOff ?? 0) / 100, 2).' '.strtoupper((string) $code->currency).' off',
        };
    }

    private function lasts(PromotionCode $code): ?string
    {
        return match ($code->duration) {
            PromotionCodes::REPEATING => $code->months.' months',
            PromotionCodes::FOREVER => 'every bill',
            PromotionCodes::ONCE => 'one bill',
            default => null,
        };
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function whole(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
