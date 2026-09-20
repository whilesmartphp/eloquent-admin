<?php

namespace Whilesmart\Admin\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Whilesmart\Admin\Models\MailTemplate;

class TemplateRegistry
{
    public function all(): array
    {
        return collect(config('admin.templates', []))
            ->map(fn (array $definition, string $key) => $this->serialize($this->findOrCreate($key), $definition))
            ->values()->all();
    }

    public function findOrCreate(string $key): MailTemplate
    {
        $definition = config("admin.templates.{$key}");
        if (! is_array($definition)) {
            throw new NotFoundHttpException('Unknown email template.');
        }
        $model = config('admin.models.mail_template', MailTemplate::class);
        $owner = config('admin.owner');

        return $model::firstOrCreate(
            ['owner_type' => $owner['type'], 'owner_id' => $owner['id'], 'key' => $key],
            [
                'enabled' => $definition['enabled'] ?? true,
                'subject' => $definition['subject'],
                'body' => $definition['body'],
                'cta_label' => $definition['cta_label'] ?? null,
                'cta_url' => $definition['cta_url'] ?? null,
            ],
        );
    }

    public function serialize(MailTemplate $template, ?array $definition = null): array
    {
        $definition ??= config("admin.templates.{$template->key}", []);

        return array_merge($template->toArray(), [
            'name' => $definition['name'] ?? $template->key,
            'description' => $definition['description'] ?? '',
            'tokens' => config('admin.tokens', []),
        ]);
    }

    public function render(MailTemplate|array $template, Authenticatable $recipient): array
    {
        $values = [
            'first_name' => (string) data_get($recipient, 'first_name'),
            'last_name' => (string) data_get($recipient, 'last_name'),
            'email' => (string) data_get($recipient, 'email'),
        ];
        $values['name'] = trim($values['first_name'].' '.$values['last_name']);
        $replace = collect($values)->mapWithKeys(fn ($value, $key) => ['{{'.$key.'}}' => $value])->all();

        return [
            'to' => $values['email'],
            'subject' => strtr((string) data_get($template, 'subject'), $replace),
            'body' => strtr((string) data_get($template, 'body'), $replace),
            'cta_label' => data_get($template, 'cta_label'),
            'cta_url' => data_get($template, 'cta_url'),
        ];
    }
}
