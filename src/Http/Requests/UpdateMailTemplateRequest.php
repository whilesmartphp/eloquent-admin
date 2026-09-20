<?php

namespace Whilesmart\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Whilesmart\Admin\Support\TemplateRegistry;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

class UpdateMailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = app(TemplateRegistry::class)->findOrCreate($this->route('key'));

        return app(OwnerAuthorizer::class)->authorize(
            $this->user(),
            $template->owner_type,
            $template->owner_id,
        );
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:10000'],
            'cta_label' => ['nullable', 'string', 'max:80'],
            'cta_url' => ['nullable', 'url', 'max:2000', 'required_with:cta_label'],
        ];
    }
}
