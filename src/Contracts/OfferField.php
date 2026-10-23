<?php

namespace Whilesmart\Admin\Contracts;

/**
 * One input a provider needs in order to create an offer.
 */
final class OfferField
{
    /**
     * @param  'text'|'number'|'date'|'select'|'boolean'|'user'  $type  'user' is
     *                                                                  an address the console can search its own directory for,
     *                                                                  while still accepting one typed in full.
     * @param  array<int|string, string>  $options  For 'select', value => label.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $type = 'text',
        public readonly bool $required = false,
        public readonly ?string $help = null,
        public readonly array $options = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'required' => $this->required,
            'help' => $this->help,
            'options' => $this->options,
        ];
    }
}
