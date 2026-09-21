<?php

namespace Whilesmart\Admin\Support;

use Whilesmart\Admin\Contracts\OfferProvider;

/**
 * The offer providers a host has registered, by key.
 *
 * Holds class names and resolves on use. The registry itself is a singleton,
 * so holding instances would keep one provider, and anything it was given,
 * alive for the life of the process.
 */
class OfferRegistry
{
    /** @var array<string, class-string<OfferProvider>> */
    private array $providers = [];

    /**
     * @param  array<int, class-string<OfferProvider>>  $providers
     */
    public function __construct(array $providers = [])
    {
        foreach ($providers as $class) {
            if (is_string($class) && is_subclass_of($class, OfferProvider::class)) {
                $this->providers[app($class)->key()] = $class;
            }
        }
    }

    /** @return array<string, OfferProvider> */
    public function all(): array
    {
        return array_map(fn (string $class) => app($class), $this->providers);
    }

    public function has(string $key): bool
    {
        return isset($this->providers[$key]);
    }

    public function get(string $key): ?OfferProvider
    {
        return isset($this->providers[$key]) ? app($this->providers[$key]) : null;
    }
}
