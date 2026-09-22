<?php

namespace Tests\Support;

use Illuminate\Foundation\Http\FormRequest;

/** A host's own validation, asking for more than the package does. */
class StrictCreateOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attributes' => ['required', 'array'],
            'attributes.reason' => ['required', 'string'],
        ];
    }
}
