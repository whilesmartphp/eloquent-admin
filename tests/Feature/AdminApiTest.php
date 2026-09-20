<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\User;
use Tests\TestCase;
use Whilesmart\Admin\Mail\TemplateMail;
use Whilesmart\UserAuthentication\Events\UserRegisteredEvent;

class AdminApiTest extends TestCase
{
    #[Test]
    public function it_returns_every_registered_measurement(): void
    {
        $this->getJson('/api/admin/metrics?days=7')
            ->assertOk()
            ->assertJsonPath('data.groups.0.key', 'users')
            ->assertJsonPath('data.groups.0.metrics.0.key', 'registered_users')
            ->assertJsonPath('data.groups.0.metrics.0.value', 12)
            ->assertJsonPath('data.groups.0.metrics.1.type', 'series');
    }

    #[Test]
    public function it_filters_measurements_by_a_registered_client(): void
    {
        $this->getJson('/api/admin/metrics?client=website')
            ->assertOk()
            ->assertJsonPath('data.selected_client', 'website')
            ->assertJsonPath('data.clients.0.name', 'Website')
            ->assertJsonPath('data.groups.0.metrics.0.value', 7);
    }

    #[Test]
    public function it_lists_users_through_the_admin_api(): void
    {
        User::query()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com']);

        $this->getJson('/api/admin/users?q=ada')
            ->assertOk()
            ->assertJsonPath('data.data.0.email', 'ada@example.com');
    }

    #[Test]
    public function it_lists_updates_and_previews_registered_templates(): void
    {
        $user = User::query()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com']);

        $this->getJson('/api/admin/mail-templates')
            ->assertOk()
            ->assertJsonPath('data.0.key', 'welcome');

        $payload = [
            'enabled' => true,
            'subject' => 'Hi {{first_name}}',
            'body' => 'Ready, {{name}}.',
            'cta_label' => 'Begin',
            'cta_url' => 'https://example.com/start',
        ];
        $this->putJson('/api/admin/mail-templates/welcome', $payload)
            ->assertOk()
            ->assertJsonPath('data.subject', 'Hi {{first_name}}');
        $this->postJson('/api/admin/mail-templates/welcome/preview', $payload + ['recipient_id' => $user->id])
            ->assertOk()
            ->assertJsonPath('data.subject', 'Hi Ada')
            ->assertJsonPath('data.body', 'Ready, Ada Lovelace.')
            ->assertJsonPath('data.cta_label', 'Begin')
            ->assertJson(fn ($json) => $json->whereType('data.html', 'string')->etc());
    }

    #[Test]
    public function registration_queues_the_registered_template(): void
    {
        Mail::fake();
        $user = User::query()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com']);

        UserRegisteredEvent::dispatch($user);

        Mail::assertQueued(TemplateMail::class, fn (TemplateMail $mail) => $mail->hasTo('ada@example.com') && $mail->mailSubject === 'Welcome Ada'
        );
    }
}
