<?php

namespace App\Notifications;

use App\Mail\TemplatedMail;
use App\Models\Lead;
use App\Notifications\Concerns\RendersTemplatedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeadAssigned extends Notification implements ShouldQueue
{
    use Queueable;
    use RendersTemplatedMail;

    public function __construct(public Lead $lead) {}

    public function via(object $notifiable): array
    {
        return $this->channelsFromPreferences($notifiable, self::class);
    }

    /**
     * @return MailMessage|TemplatedMail
     */
    public function toMail(object $notifiable): MailMessage|TemplatedMail
    {
        return $this->templatedMail($notifiable, 'lead_assigned', [
            'user_name' => $notifiable->name,
            'lead_name' => $this->lead->name,
            'lead_company' => $this->lead->company ?? '—',
            'lead_email' => $this->lead->email ?? '—',
            'lead_phone' => $this->lead->phone ?? '—',
            'lead_url' => route('leads.show', $this->lead),
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subject' => 'Lead assigned to you',
            'message' => 'You have been assigned the lead '.$this->lead->name.'.',
            'url' => route('leads.show', $this->lead, false),
            'lead_id' => $this->lead->id,
            'lead_name' => $this->lead->name,
        ];
    }
}
