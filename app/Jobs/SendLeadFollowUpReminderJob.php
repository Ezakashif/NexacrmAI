<?php

namespace App\Jobs;

use App\Services\LeadFollowUpReminderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendLeadFollowUpReminderJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public int $leadId,
        public string $tier,
    ) {}

    public function handle(LeadFollowUpReminderService $reminders): void
    {
        $reminders->deliverReminder($this->leadId, $this->tier);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Lead follow-up reminder job failed.', [
            'lead_id' => $this->leadId,
            'tier' => $this->tier,
            'error' => $exception?->getMessage(),
        ]);
    }
}
