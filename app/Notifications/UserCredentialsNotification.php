<?php

namespace App\Notifications;

use App\Mail\TemplatedMail;
use App\Notifications\Concerns\RendersTemplatedMail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent synchronously so the temporary password is not persisted on a queue.
 */
class UserCredentialsNotification extends Notification
{
    use RendersTemplatedMail;

    public function __construct(
        public string $temporaryPassword,
        public string $companyName,
        public string $roleNames = '',
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @return MailMessage|TemplatedMail
     */
    public function toMail(object $notifiable): MailMessage|TemplatedMail
    {
        return $this->templatedMail($notifiable, 'user_credentials', [
            'user_name' => $notifiable->name,
            'user_email' => $notifiable->email,
            'temporary_password' => $this->temporaryPassword,
            'company_name' => $this->companyName,
            'role_names' => $this->roleNames,
            'login_url' => route('login'),
            'subject' => 'Your '.config('app.name').' account credentials',
            'body' => 'Your account has been created. Sign in with the email and password provided by your administrator.',
        ]);
    }
}
