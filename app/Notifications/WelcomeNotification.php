<?php

namespace App\Notifications;

use App\Mail\TemplatedMail;
use App\Notifications\Concerns\RendersTemplatedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RendersTemplatedMail;

    public function __construct(
        public string $companyName,
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
        return $this->templatedMail($notifiable, 'welcome', [
            'user_name' => $notifiable->name,
            'user_email' => $notifiable->email,
            'company_name' => $this->companyName,
            'login_url' => route('login'),
        ]);
    }
}
