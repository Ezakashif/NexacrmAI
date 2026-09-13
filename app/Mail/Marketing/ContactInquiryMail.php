<?php

namespace App\Mail\Marketing;

use App\Services\Email\EmailTemplateRenderer;
use App\Services\Email\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactInquiryMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array{name: string, email: string, company: ?string, phone: ?string, message: string, intent: ?string}  $inquiry
     */
    public function __construct(public array $inquiry) {}

    public function envelope(): Envelope
    {
        $rendered = $this->rendered();
        $intent = $this->inquiry['intent'] ?? null;

        $subject = $rendered['template'] !== null
            ? $rendered['subject']
            : ($intent === 'demo'
                ? 'Algos demo request from '.$this->inquiry['name']
                : 'Algos contact inquiry from '.$this->inquiry['name']);

        return new Envelope(
            subject: $subject,
            replyTo: [$this->inquiry['email']],
        );
    }

    public function content(): Content
    {
        $rendered = $this->rendered();

        if ($rendered['template'] !== null) {
            $brandedHtml = app(EmailTemplateRenderer::class)->wrapBranded(
                $rendered['html'],
                $rendered['subject'],
                $rendered['use_branding'],
            );

            return new Content(
                htmlString: $brandedHtml,
                text: 'emails.templated-text',
                with: ['textBody' => $rendered['text']],
            );
        }

        return new Content(
            text: 'emails.marketing.contact-inquiry',
        );
    }

    /**
     * @return array{subject: string, html: string, text: string, template: mixed, use_branding: bool}
     */
    private function rendered(): array
    {
        return app(EmailTemplateService::class)->render('contact_inquiry', [
            'inquiry_name' => $this->inquiry['name'],
            'inquiry_email' => $this->inquiry['email'],
            'inquiry_company' => $this->inquiry['company'] ?: '—',
            'inquiry_phone' => $this->inquiry['phone'] ?: '—',
            'inquiry_intent' => $this->inquiry['intent'] ?: 'general',
            'inquiry_message' => $this->inquiry['message'],
        ]);
    }
}
