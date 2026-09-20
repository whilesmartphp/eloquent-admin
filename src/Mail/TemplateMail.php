<?php

namespace Whilesmart\Admin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class TemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $body,
        public ?string $ctaLabel = null,
        public ?string $ctaUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(view: 'admin::template-mail', with: [
            'bodyHtml' => Str::markdown($this->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
            'ctaLabel' => $this->ctaLabel,
            'ctaUrl' => $this->ctaUrl,
        ]);
    }
}
