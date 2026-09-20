<?php

namespace Whilesmart\Admin\Listeners;

use Illuminate\Support\Facades\Mail;
use Whilesmart\Admin\Mail\TemplateMail;
use Whilesmart\Admin\Support\TemplateRegistry;
use Whilesmart\UserAuthentication\Events\UserRegisteredEvent;

class SendRegistrationEmail
{
    public function handle(UserRegisteredEvent $event): void
    {
        if (! config('admin.send_registration_email', true)) {
            return;
        }
        $template = app(TemplateRegistry::class)->findOrCreate(config('admin.registration_template', 'welcome'));
        if (! $template->enabled) {
            return;
        }
        $rendered = app(TemplateRegistry::class)->render($template, $event->user);
        Mail::to($rendered['to'])->queue(new TemplateMail(
            $rendered['subject'],
            $rendered['body'],
            $rendered['cta_label'],
            $rendered['cta_url'],
        ));
    }
}
