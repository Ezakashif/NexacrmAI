<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Services\UserNotificationPreferenceService;
use Illuminate\Notifications\Notification;

class WebsiteLeadReceived extends Notification
{
    public function __construct(public Lead $lead) {}

    public function via(object $notifiable): array
    {
        return app(UserNotificationPreferenceService::class)->isEnabled($notifiable, self::class, 'database')
            ? ['database']
            : [];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subject' => 'New website lead',
            'message' => 'A new website lead, '.$this->lead->name.', was received.',
            'url' => route('leads.show', $this->lead, false),
            'lead_id' => $this->lead->id,
            'lead_name' => $this->lead->name,
        ];
    }
}
