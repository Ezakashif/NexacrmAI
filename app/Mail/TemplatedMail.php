<?php

namespace App\Mail;

use App\Services\Email\EmailTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemplatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $htmlBody,
        public string $textBody,
        public bool $useBranding = true,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        $brandedHtml = app(EmailTemplateRenderer::class)->wrapBranded(
            $this->htmlBody,
            $this->subjectLine,
            $this->useBranding,
        );

        return new Content(
            htmlString: $brandedHtml,
            text: 'emails.templated-text',
            with: [
                'textBody' => $this->textBody,
            ],
        );
    }
}
