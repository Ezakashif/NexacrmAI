<?php

namespace App\Notifications;

use App\Mail\TemplatedMail;
use App\Notifications\Concerns\RendersTemplatedMail;
use App\Support\EmailVerification;
use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Sent synchronously — auth activation must not depend on a queue worker.
 */
class AccountActivationNotification extends BaseVerifyEmail
{
    use RendersTemplatedMail;

    /**
     * @return MailMessage|TemplatedMail
     */
    public function toMail(mixed $notifiable): MailMessage|TemplatedMail
    {
        $url = EmailVerification::signedUrl($notifiable);

        return $this->templatedMail($notifiable, 'account_activation', [
            'user_name' => $notifiable->name,
            'user_email' => $notifiable->getEmailForVerification(),
            'activation_url' => $url,
            'expires_minutes' => (string) config('auth.verification.expire', 60),
        ]);
    }
}
