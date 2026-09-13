<?php

namespace App\Jobs;

use App\Services\TaskReminderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTaskReminderJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public int $taskId,
        public string $tier,
    ) {}

    public function handle(TaskReminderService $reminders): void
    {
        $reminders->deliverReminder($this->taskId, $this->tier);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Task reminder job failed.', [
            'task_id' => $this->taskId,
            'tier' => $this->tier,
            'error' => $exception?->getMessage(),
        ]);
    }
}
