<?php

namespace App\Jobs;

use App\Services\SuperAdmin\PlatformAlertNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPlatformAlertJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $uniqueFor = 900;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    /**
     * @param  array{type: string, severity: string, title: string, message: string, meta?: array<string, mixed>}  $alert
     */
    public function __construct(public array $alert) {}

    public function handle(PlatformAlertNotificationService $notifications): void
    {
        $notifications->deliverCurrent($this->alert);
    }

    public function uniqueId(): string
    {
        return hash('sha256', json_encode($this->alert, JSON_THROW_ON_ERROR));
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Platform alert notification job failed.', [
            'alert_type' => $this->alert['type'] ?? null,
            'error' => $exception?->getMessage(),
        ]);
    }
}
