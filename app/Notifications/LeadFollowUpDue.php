<?php

namespace App\Notifications;

use App\Mail\TemplatedMail;
use App\Models\Lead;
use App\Notifications\Concerns\RendersTemplatedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeadFollowUpDue extends Notification implements ShouldQueue
{
    use Queueable;
    use RendersTemplatedMail;

    public function __construct(
        public Lead $lead,
        public string $tier = 'due',
    ) {}

    public function via(object $notifiable): array
    {
        return $this->channelsFromPreferences($notifiable, self::class);
    }

    /**
     * @return MailMessage|TemplatedMail
     */
    public function toMail(object $notifiable): MailMessage|TemplatedMail
    {
        [$subject, $line] = $this->copy();

        return $this->templatedMail($notifiable, 'lead_follow_up', [
            'user_name' => $notifiable->name,
            'lead_name' => $this->lead->name,
            'lead_company' => $this->lead->company ?? '—',
            'lead_email' => $this->lead->email ?? '—',
            'lead_phone' => $this->lead->phone ?? '—',
            'follow_up_date' => $this->lead->follow_up_date?->format('M j, Y') ?? 'unknown date',
            'subject_line' => $subject,
            'body_line' => $line,
            'lead_url' => route('leads.show', $this->lead),
        ]);
    }

    public function toArray(object $notifiable): array
    {
        [$subject, $line] = $this->copy();

        return [
            'lead_id' => $this->lead->id,
            'lead_name' => $this->lead->name,
            'follow_up_date' => $this->lead->follow_up_date?->toDateString(),
            'tier' => $this->tier,
            'is_overdue' => $this->isOverdue(),
            'message' => $line,
            'url' => route('leads.show', $this->lead, false),
            'subject' => $subject,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function copy(): array
    {
        $dateLabel = $this->lead->follow_up_date?->format('M j, Y') ?? 'unknown date';

        return match ($this->tier) {
            'day_before' => [
                'Follow-up tomorrow',
                'Reminder: follow-up for '.$this->lead->name.' is scheduled for tomorrow ('.$dateLabel.').',
            ],
            'hours_before' => [
                'Follow-up in 2 hours',
                'Reminder: follow-up for '.$this->lead->name.' is coming up in about 2 hours ('.$dateLabel.').',
            ],
            'overdue' => [
                'Follow-up still overdue',
                'Follow-up for '.$this->lead->name.' remains overdue since '.$dateLabel.'.',
            ],
            default => $this->isOverdue()
                ? [
                    'Follow-up overdue',
                    'You have a lead follow-up that was due on '.$dateLabel.'.',
                ]
                : [
                    'Follow-up due today',
                    'You have a lead follow-up that is due today ('.$dateLabel.').',
                ],
        };
    }

    protected function isOverdue(): bool
    {
        if (! $this->lead->follow_up_date) {
            return false;
        }

        return $this->lead->follow_up_date->isPast() && ! $this->lead->follow_up_date->isToday();
    }
}
