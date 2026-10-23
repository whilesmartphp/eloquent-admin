<?php

namespace Whilesmart\Admin\Offers;

use Illuminate\Support\Carbon;
use Whilesmart\Admin\Contracts\OfferField;
use Whilesmart\Admin\Contracts\OfferProvider;
use Whilesmart\Admin\Support\Offer;
use Whilesmart\Entitlements\Exceptions\FreePassException;
use Whilesmart\Entitlements\Models\Plan;
use Whilesmart\Entitlements\Models\Subscription;
use Whilesmart\Entitlements\Services\FreePassService;

/**
 * Plans given to a person without payment, from whilesmart/eloquent-entitlements.
 * The pass belongs to the user, so a host whose plans belong to a user's
 * workspaces resolves them through that user.
 */
class FreePasses implements OfferProvider
{
    public function __construct(private readonly FreePassService $passes) {}

    public function key(): string
    {
        return 'free-passes';
    }

    public function label(): string
    {
        return 'Free passes';
    }

    /** A pass is held by a person and has no code, use count or length to show. */
    public function columns(): array
    {
        return ['code' => 'Person', 'value' => 'Plan', 'until' => 'Until'];
    }

    public function fields(): array
    {
        return [
            new OfferField('person', 'Person', 'user', required: true),
            new OfferField('plan', 'Plan', 'select', required: true, options: $this->planModel()::query()->orderBy('id')->pluck('name', 'key')->all()),
            new OfferField('until', 'Until', 'date', help: 'Left empty, it lasts until stopped.'),
        ];
    }

    public function all(): array
    {
        return $this->passes->all()->map(fn (Subscription $pass) => $this->asOffer($pass))->all();
    }

    public function create(array $attributes): Offer
    {
        $email = trim((string) ($attributes['person'] ?? ''));
        $users = config('admin.user_model');
        $person = $email === '' ? null : $users::query()->where('email', $email)->first();
        abort_if($person === null, 422, "Nobody here uses the address {$email}.");

        $plan = $this->planModel()::query()->where('key', (string) ($attributes['plan'] ?? ''))->first();
        abort_if($plan === null, 422, 'Choose a plan.');

        $until = filled($attributes['until'] ?? null) ? Carbon::parse((string) $attributes['until'])->endOfDay() : null;

        try {
            $pass = $this->passes->grant($person, $plan, $until);
        } catch (FreePassException $refused) {
            abort(422, $refused->getMessage());
        }

        return $this->asOffer($pass->load(['owner', 'plan']));
    }

    public function revoke(string $id): void
    {
        try {
            $this->passes->stop($id);
        } catch (FreePassException $refused) {
            abort(422, $refused->getMessage());
        }
    }

    private function asOffer(Subscription $pass): Offer
    {
        return new Offer(
            id: (string) $pass->getKey(),
            code: (string) ($pass->owner?->email ?? ''),
            value: (string) ($pass->plan?->name ?? ''),
            expiresAt: $pass->current_period_end,
            active: $this->passes->running($pass),
            meta: ['plan' => $pass->plan?->key],
        );
    }

    /** @return class-string<Plan> */
    private function planModel(): string
    {
        return config('entitlements.models.plan', Plan::class);
    }
}
