<?php

namespace App\Notifications;

use App\Services\UserNotificationPreferenceService;
use Illuminate\Notifications\Notification;

class PlatformAlertDetected extends Notification
{
    /**
     * @param  array{type: string, severity: string, title: string, message: string, meta?: array<string, mixed>}  $alert
     */
    public function __construct(private readonly array $alert) {}

    public function via(object $notifiable): array
    {
        $types = app(UserNotificationPreferenceService::class)->types();
        $isConfigured = in_array(self::class, array_column($types, 'class'), true);

        if (! $isConfigured) {
            return ['database'];
        }

        return app(UserNotificationPreferenceService::class)->isEnabled($notifiable, self::class, 'database')
            ? ['database']
            : [];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subject' => $this->alert['title'],
            'message' => $this->alert['message'],
            'severity' => $this->alert['severity'],
            'alert_type' => $this->alert['type'],
            'url' => route('superadmin.dashboard', [], false),
        ];
    }
}
