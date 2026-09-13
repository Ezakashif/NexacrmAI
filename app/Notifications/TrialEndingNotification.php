<?php

namespace App\Notifications;

use App\Mail\TemplatedMail;
use App\Models\Company;
use App\Notifications\Concerns\RendersTemplatedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialEndingNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RendersTemplatedMail;

    public function __construct(
        public Company $company,
        public int $daysRemaining,
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
        return $this->templatedMail($notifiable, 'trial_ending', [
            'user_name' => $notifiable->name,
            'company_name' => $this->company->name,
            'trial_ends_at' => $this->company->trial_ends_at?->format('M j, Y') ?? '',
            'days_remaining' => (string) $this->daysRemaining,
            'billing_url' => route('dashboard'),
        ]);
    }
}
