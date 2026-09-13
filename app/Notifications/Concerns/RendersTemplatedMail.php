<?php

namespace App\Notifications\Concerns;

use App\Mail\TemplatedMail;
use App\Services\Email\EmailTemplateService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

trait RendersTemplatedMail
{
    /**
     * @param  array<string, scalar|null>  $placeholders
     */
    protected function templatedMail(
        object $notifiable,
        string $category,
        array $placeholders = [],
        ?string $locale = null,
    ): MailMessage|TemplatedMail {
        $locale ??= data_get($notifiable, 'language') ?: config('email_templates.default_locale', 'en');
        $service = app(EmailTemplateService::class);
        $content = $service->mailContent($category, $placeholders, $locale);
        $recipient = $this->mailRecipient($notifiable);

        if ($content === null) {
            $message = (new MailMessage)
                ->subject((string) ($placeholders['subject'] ?? ucfirst(str_replace('_', ' ', $category))))
                ->greeting('Hello'.(data_get($notifiable, 'name') ? ' '.data_get($notifiable, 'name') : '').'!');

            if (isset($placeholders['activation_url'])) {
                return $message
                    ->line('Please confirm your email address to activate your account.')
                    ->action('Activate account', (string) $placeholders['activation_url'])
                    ->line('If you did not create an account, no further action is required.');
            }

            if (isset($placeholders['reset_url'])) {
                return $message
                    ->line('You are receiving this email because we received a password reset request for your account.')
                    ->action('Reset password', (string) $placeholders['reset_url'])
                    ->line('If you did not request a password reset, no further action is required.');
            }

            if (isset($placeholders['temporary_password'])) {
                return $message
                    ->line('An account was created for you'.(isset($placeholders['company_name']) ? ' on '.$placeholders['company_name'] : '').'.')
                    ->line('Email: '.(string) ($placeholders['user_email'] ?? data_get($notifiable, 'email')))
                    ->line('Temporary password: '.(string) $placeholders['temporary_password'])
                    ->action('Sign in', (string) ($placeholders['login_url'] ?? route('login')))
                    ->line('Please change your password after signing in.');
            }

            return $message->line((string) ($placeholders['body'] ?? 'Notification from '.config('app.name')));
        }

        // MailChannel does not attach recipients when a Mailable is returned.
        return (new TemplatedMail(
            subjectLine: $content['subject'],
            htmlBody: (string) $content['html'],
            textBody: $content['text'],
            useBranding: $service->resolve($category, $locale)?->use_branding ?? true,
        ))->to($recipient);
    }

    /**
     * @return string|array<int, string|array{name?: string, email: string}>
     */
    protected function mailRecipient(object $notifiable): string|array
    {
        $route = $notifiable->routeNotificationFor('mail', $this instanceof Notification ? $this : null);

        if (filled($route)) {
            return $route;
        }

        $email = data_get($notifiable, 'email');

        if (filled($email)) {
            return (string) $email;
        }

        return config('mail.from.address');
    }

    /**
     * @param  list<string>  $preferenceChannels  Values like database, email
     * @return list<string>  Laravel channels: database, mail
     */
    protected function channelsFromPreferences(object $notifiable, string $notificationClass, array $preferenceChannels = ['database', 'email']): array
    {
        $preferences = app(\App\Services\UserNotificationPreferenceService::class);
        $channels = [];

        foreach ($preferenceChannels as $preferenceChannel) {
            if (! $preferences->isEnabled($notifiable, $notificationClass, $preferenceChannel)) {
                continue;
            }

            $channels[] = $preferenceChannel === 'email' ? 'mail' : $preferenceChannel;
        }

        return $channels;
    }
}
