<?php

namespace App\Console\Commands;

use App\Services\SuperAdmin\PlatformAlertNotificationService;
use Illuminate\Console\Command;

class SendPlatformAlertNotifications extends Command
{
    protected $signature = 'platform:send-alert-notifications';

    protected $description = 'Queue danger-severity platform alerts for active Super Admins';

    public function handle(PlatformAlertNotificationService $notifications): int
    {
        $dispatched = $notifications->dispatchDangerAlerts();

        $this->info("Dispatched {$dispatched} platform alert notification job(s).");

        return self::SUCCESS;
    }
}
