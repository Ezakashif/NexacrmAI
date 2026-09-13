<?php

namespace App\Services\Email;

use App\Mail\TemplatedMail;
use App\Models\EmailSendLog;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\SuperAdmin\PlatformSettingsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;
use Throwable;

class EmailTemplateService
{
    public function __construct(
        private readonly EmailTemplateRenderer $renderer,
        private readonly PlatformSettingsService $platformSettings,
    ) {}

    public function resolve(string $category, ?string $locale = null): ?EmailTemplate
    {
        if (! Schema::hasTable('email_templates')) {
            return null;
        }

        $locale = $locale ?: config('email_templates.default_locale', 'en');
        $fallback = config('email_templates.default_locale', 'en');

        $template = EmailTemplate::query()
            ->active()
            ->forCategory($category)
            ->forLocale($locale)
            ->first();

        if ($template || $locale === $fallback) {
            return $template;
        }

        return EmailTemplate::query()
            ->active()
            ->forCategory($category)
            ->forLocale($fallback)
            ->first();
    }

    /**
     * @param  array<string, scalar|null>  $placeholders
     * @return array{subject: string, html: string, text: string, template: EmailTemplate|null, use_branding: bool}
     */
    public function render(string $category, array $placeholders = [], ?string $locale = null): array
    {
        $template = $this->resolve($category, $locale);
        $placeholders = $this->withPlatformDefaults($placeholders);

        if (! $template) {
            return [
                'subject' => (string) ($placeholders['subject'] ?? ucfirst(str_replace('_', ' ', $category))),
                'html' => (string) ($placeholders['body'] ?? ''),
                'text' => strip_tags((string) ($placeholders['body'] ?? '')),
                'template' => null,
                'use_branding' => true,
            ];
        }

        return [
            'subject' => $this->renderer->replace($template->subject, $placeholders),
            'html' => $this->renderer->replace($template->html_body, $placeholders),
            'text' => $this->renderer->replace($template->text_body ?: strip_tags($template->html_body), $placeholders),
            'template' => $template,
            'use_branding' => (bool) $template->use_branding,
        ];
    }

    /**
     * @param  array<string, scalar|null>  $placeholders
     */
    public function previewHtml(EmailTemplate $template, ?array $placeholders = null): string
    {
        $placeholders = $this->withPlatformDefaults(
            $placeholders ?? $this->samplePlaceholders($template->category)
        );

        $html = $this->renderer->replace($template->html_body, $placeholders);

        return $this->renderer->wrapBranded(
            $html,
            $this->renderer->replace($template->subject, $placeholders),
            (bool) $template->use_branding
        );
    }

    /**
     * @param  array<string, scalar|null>  $placeholders
     */
    public function sendTemplated(
        string $category,
        string $toEmail,
        array $placeholders = [],
        ?string $locale = null,
        ?User $triggeredBy = null,
        ?Model $related = null,
        bool $queue = true,
        string $logStatus = EmailSendLog::STATUS_QUEUED,
    ): bool {
        $rendered = $this->render($category, $placeholders, $locale);
        $mailable = new TemplatedMail(
            subjectLine: $rendered['subject'],
            htmlBody: $rendered['html'],
            textBody: $rendered['text'],
            useBranding: $rendered['use_branding'],
        );

        try {
            if ($queue) {
                Mail::to($toEmail)->queue($mailable);
            } else {
                Mail::to($toEmail)->sendNow($mailable);
                $logStatus = $logStatus === EmailSendLog::STATUS_QUEUED
                    ? EmailSendLog::STATUS_SENT
                    : $logStatus;
            }

            $this->logSend(
                template: $rendered['template'],
                category: $category,
                locale: $locale ?: config('email_templates.default_locale', 'en'),
                toEmail: $toEmail,
                subject: $rendered['subject'],
                status: $logStatus,
                placeholders: $placeholders,
                triggeredBy: $triggeredBy,
                related: $related,
            );

            return true;
        } catch (Throwable $e) {
            report($e);

            $this->logSend(
                template: $rendered['template'],
                category: $category,
                locale: $locale ?: config('email_templates.default_locale', 'en'),
                toEmail: $toEmail,
                subject: $rendered['subject'],
                status: EmailSendLog::STATUS_FAILED,
                placeholders: $placeholders,
                triggeredBy: $triggeredBy,
                related: $related,
                error: $e->getMessage(),
            );

            return false;
        }
    }

    /**
     * @param  array<string, scalar|null>|null  $placeholders
     */
    public function sendTest(EmailTemplate $template, string $toEmail, ?User $triggeredBy = null, ?array $placeholders = null): bool
    {
        $placeholders = $this->withPlatformDefaults(
            $placeholders ?? $this->samplePlaceholders($template->category)
        );

        $ok = $this->sendTemplated(
            category: $template->category,
            toEmail: $toEmail,
            placeholders: $placeholders,
            locale: $template->locale,
            triggeredBy: $triggeredBy,
            queue: false,
            logStatus: EmailSendLog::STATUS_TEST,
        );

        ActivityLogger::log('email_template.test_sent', $template, [
            'to' => $toEmail,
            'category' => $template->category,
            'locale' => $template->locale,
        ], $triggeredBy?->id);

        return $ok;
    }

    /**
     * @return array<string, string>
     */
    public function samplePlaceholders(string $category): array
    {
        $sample = config("email_templates.categories.{$category}.sample", []);

        return is_array($sample) ? $sample : [];
    }

    /**
     * Build a Laravel MailMessage-compatible HtmlString body from a template.
     *
     * @param  array<string, scalar|null>  $placeholders
     * @return array{subject: string, html: HtmlString, text: string}|null
     */
    public function mailContent(string $category, array $placeholders = [], ?string $locale = null): ?array
    {
        $template = $this->resolve($category, $locale);

        if (! $template) {
            return null;
        }

        $rendered = $this->render($category, $placeholders, $locale);

        return [
            'subject' => $rendered['subject'],
            'html' => new HtmlString($rendered['html']),
            'text' => $rendered['text'],
        ];
    }

    /**
     * @param  array<string, scalar|null>  $placeholders
     * @return array<string, scalar|null>
     */
    public function withPlatformDefaults(array $placeholders): array
    {
        $placeholders['platform_name'] ??= $this->platformSettings->platformName();
        $placeholders['support_email'] ??= $this->platformSettings->get('mail_from_address')
            ?: config('mail.from.address');

        foreach ($placeholders as $key => $value) {
            if (is_string($value) && str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
                $placeholders[$key] = url($value);
            }
        }

        return $placeholders;
    }

    /**
     * @param  array<string, scalar|null>  $placeholders
     */
    private function logSend(
        ?EmailTemplate $template,
        string $category,
        string $locale,
        string $toEmail,
        string $subject,
        string $status,
        array $placeholders,
        ?User $triggeredBy = null,
        ?Model $related = null,
        ?string $error = null,
    ): void {
        if (! Schema::hasTable('email_send_logs')) {
            return;
        }

        EmailSendLog::query()->create([
            'email_template_id' => $template?->id,
            'category' => $category,
            'locale' => $locale,
            'to_email' => $toEmail,
            'subject' => $subject,
            'status' => $status,
            'mailer' => config('mail.default'),
            'error_message' => $error,
            'placeholders' => $placeholders,
            'triggered_by' => $triggeredBy?->id ?? auth()->id(),
            'related_type' => $related?->getMorphClass(),
            'related_id' => $related?->getKey(),
        ]);
    }
}
