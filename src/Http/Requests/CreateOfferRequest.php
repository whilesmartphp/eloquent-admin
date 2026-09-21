<?php

namespace Whilesmart\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Only the shape. What each field means is the provider's to say. */
    public function rules(): array
    {
        return [
            'attributes' => ['required', 'array'],
        ];
    }
}
