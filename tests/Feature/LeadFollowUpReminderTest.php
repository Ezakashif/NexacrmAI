<?php

namespace Tests\Feature;

use App\Jobs\SendLeadFollowUpReminderJob;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadFollowUpDue;
use App\Services\LeadFollowUpReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LeadFollowUpReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_sends_due_notification_to_assigned_user(): void
    {
        Notification::fake();

        $user = User::factory()->create(['status' => 'active']);
        $lead = Lead::factory()->assignedTo($user)->create([
            'follow_up_date' => today(),
            'status' => 'new',
            'follow_up_reminders_sent' => null,
        ]);

        $this->artisan('leads:send-follow-up-reminders --tier=due')->assertSuccessful();

        Notification::assertSentTo($user, LeadFollowUpDue::class, function (LeadFollowUpDue $notification) use ($lead) {
            return $notification->lead->is($lead) && $notification->tier === 'due';
        });

        $this->assertTrue($lead->fresh()->hasFollowUpReminderBeenSent('due'));
    }

    public function test_command_sends_one_day_before_reminder(): void
    {
        Notification::fake();

        $user = User::factory()->create(['status' => 'active']);
        $lead = Lead::factory()->assignedTo($user)->create([
            'follow_up_date' => today()->addDay(),
            'status' => 'contacted',
            'follow_up_reminders_sent' => null,
        ]);

        $this->artisan('leads:send-follow-up-reminders --tier=day_before')->assertSuccessful();

        Notification::assertSentTo($user, LeadFollowUpDue::class, function (LeadFollowUpDue $notification) use ($lead) {
            return $notification->lead->is($lead) && $notification->tier === 'day_before';
        });

        $this->assertTrue($lead->fresh()->hasFollowUpReminderBeenSent('day_before'));
        $this->assertFalse($lead->fresh()->hasFollowUpReminderBeenSent('due'));
    }

    public function test_command_sends_two_hours_before_reminder_inside_window(): void
    {
        Notification::fake();
        config(['lead_reminders.default_follow_up_time' => '09:00']);

        $this->travelTo(now()->setTime(7, 15, 0));

        $user = User::factory()->create(['status' => 'active']);
        $lead = Lead::factory()->assignedTo($user)->create([
            'follow_up_date' => today(),
            'status' => 'qualified',
            'follow_up_reminders_sent' => null,
        ]);

        $this->artisan('leads:send-follow-up-reminders --tier=hours_before')->assertSuccessful();

        Notification::assertSentTo($user, LeadFollowUpDue::class, function (LeadFollowUpDue $notification) use ($lead) {
            return $notification->lead->is($lead) && $notification->tier === 'hours_before';
        });

        $this->assertTrue($lead->fresh()->hasFollowUpReminderBeenSent('hours_before'));
    }

    public function test_two_hours_before_skips_outside_window(): void
    {
        Notification::fake();
        config(['lead_reminders.default_follow_up_time' => '09:00']);

        $this->travelTo(now()->setTime(10, 0, 0));

        $user = User::factory()->create(['status' => 'active']);
        Lead::factory()->assignedTo($user)->create([
            'follow_up_date' => today(),
            'status' => 'new',
            'follow_up_reminders_sent' => null,
        ]);

        $this->artisan('leads:send-follow-up-reminders --tier=hours_before')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_command_does_not_send_duplicate_reminders(): void
    {
        Notification::fake();

        $user = User::factory()->create(['status' => 'active']);
        Lead::factory()->assignedTo($user)->create([
            'follow_up_date' => today(),
            'follow_up_reminders_sent' => ['due' => now()->toIso8601String()],
        ]);

        $this->artisan('leads:send-follow-up-reminders --tier=due')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_overdue_escalation_repeats_daily_only_after_the_one_shot_due_reminder(): void
    {
        Notification::fake();
        config(['lead_reminders.tiers.overdue.repeat_days' => 1]);

        $user = User::factory()->create(['status' => 'active']);
        $lead = Lead::factory()->assignedTo($user)->create([
            'follow_up_date' => today()->subDay(),
            'status' => 'new',
            'follow_up_reminders_sent' => ['due' => now()->subDay()->toIso8601String()],
        ]);

        $this->artisan('leads:send-follow-up-reminders --tier=overdue')->assertSuccessful();
        $this->artisan('leads:send-follow-up-reminders --tier=overdue')->assertSuccessful();

        Notification::assertSentTo($user, LeadFollowUpDue::class, function (LeadFollowUpDue $notification) use ($lead) {
            return $notification->lead->is($lead) && $notification->tier === 'overdue';
        });
        $this->assertTrue($lead->fresh()->hasFollowUpReminderBeenSent('overdue'));

        $this->travel(1)->days();
        $this->artisan('leads:send-follow-up-reminders --tier=overdue')->assertSuccessful();

        Notification::assertSentTo($user, LeadFollowUpDue::class, 2);
    }

    public function test_command_skips_won_leads(): void
    {
        Notification::fake();

        $user = User::factory()->create(['status' => 'active']);
        Lead::factory()->assignedTo($user)->create([
            'follow_up_date' => today(),
            'status' => 'won',
            'follow_up_reminders_sent' => null,
        ]);

        $this->artisan('leads:send-follow-up-reminders --tier=due')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_changing_follow_up_date_resets_reminder_flags(): void
    {
        $lead = Lead::factory()->create([
            'follow_up_date' => today(),
            'follow_up_reminders_sent' => [
                'due' => now()->toIso8601String(),
                'day_before' => now()->toIso8601String(),
            ],
        ]);

        $lead->update(['follow_up_date' => today()->addDays(5)]);

        $this->assertNull($lead->fresh()->follow_up_reminders_sent);
    }

    public function test_changing_assignee_resets_reminder_flags(): void
    {
        $original = User::factory()->create();
        $replacement = User::factory()->create();

        $lead = Lead::factory()->assignedTo($original)->create([
            'follow_up_date' => today(),
            'follow_up_reminders_sent' => ['due' => now()->toIso8601String()],
        ]);

        $lead->update(['assigned_to' => $replacement->id]);

        $this->assertNull($lead->fresh()->follow_up_reminders_sent);
    }

    public function test_deliver_reminder_marks_tier_only_after_success(): void
    {
        Notification::fake();

        $user = User::factory()->create(['status' => 'active']);
        $lead = Lead::factory()->assignedTo($user)->create([
            'follow_up_date' => today(),
            'status' => 'new',
            'follow_up_reminders_sent' => null,
        ]);

        $service = app(LeadFollowUpReminderService::class);

        $this->assertFalse($lead->hasFollowUpReminderBeenSent('due'));
        $this->assertTrue($service->deliverReminder($lead->id, 'due'));
        $this->assertTrue($lead->fresh()->hasFollowUpReminderBeenSent('due'));

        // Second delivery is a no-op once marked.
        $this->assertFalse($service->deliverReminder($lead->id, 'due'));
    }

    public function test_job_failed_handler_does_not_mark_reminder_sent(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $lead = Lead::factory()->assignedTo($user)->create([
            'follow_up_date' => today(),
            'status' => 'new',
            'follow_up_reminders_sent' => null,
        ]);

        $job = new SendLeadFollowUpReminderJob($lead->id, 'due');
        $job->failed(new \RuntimeException('queue worker crashed'));

        $this->assertFalse($lead->fresh()->hasFollowUpReminderBeenSent('due'));
    }

    public function test_command_dispatches_queued_jobs_when_queue_is_async(): void
    {
        Queue::fake();

        $user = User::factory()->create(['status' => 'active']);
        Lead::factory()->assignedTo($user)->create([
            'follow_up_date' => today(),
            'status' => 'new',
            'follow_up_reminders_sent' => null,
        ]);

        $this->artisan('leads:send-follow-up-reminders --tier=due')->assertSuccessful();

        Queue::assertPushed(SendLeadFollowUpReminderJob::class, 1);
    }

    public function test_user_can_view_notifications_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Notifications');
    }
}
