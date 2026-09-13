<?php

namespace Tests\Unit\Models;

use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadFollowUpReminderStateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        config(['lead_reminders.default_follow_up_time' => '09:00']);
    }

    public function test_follow_up_reminder_sent_helpers_track_tier_timestamps(): void
    {
        $lead = Lead::factory()->create([
            'follow_up_reminders_sent' => null,
        ]);

        $this->assertFalse($lead->hasFollowUpReminderBeenSent('due'));
        $this->assertNull($lead->followUpReminderSentAt('due'));

        $lead->markFollowUpReminderSent('due');
        $lead->refresh();

        $this->assertTrue($lead->hasFollowUpReminderBeenSent('due'));
        $this->assertNotNull($lead->followUpReminderSentAt('due'));
        $this->assertFalse($lead->hasFollowUpReminderBeenSent('day_before'));
    }

    public function test_eligible_scope_includes_due_leads_without_sent_tier(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $eligibleLead = Lead::factory()->assignedTo($user)->create([
            'follow_up_date' => today(),
            'status' => 'contacted',
            'follow_up_reminders_sent' => null,
        ]);

        Lead::factory()->assignedTo($user)->create([
            'follow_up_date' => today(),
            'follow_up_reminders_sent' => ['due' => now()->toIso8601String()],
        ]);

        $ids = Lead::query()->eligibleForFollowUpReminderTier('due')->pluck('id')->all();

        $this->assertSame([$eligibleLead->id], $ids);
    }

    public function test_eligible_scope_excludes_won_and_lost_leads(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Lead::factory()->assignedTo($user)->create([
            'follow_up_date' => today(),
            'status' => 'won',
        ]);
        Lead::factory()->assignedTo($user)->create([
            'follow_up_date' => today(),
            'status' => 'lost',
        ]);

        $this->assertCount(0, Lead::query()->eligibleForFollowUpReminderTier('due')->get());
    }

    public function test_eligible_scope_day_before_matches_tomorrow(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $lead = Lead::factory()->assignedTo($user)->create([
            'follow_up_date' => today()->addDay(),
            'status' => 'new',
        ]);

        Lead::factory()->assignedTo($user)->create([
            'follow_up_date' => today(),
            'status' => 'new',
        ]);

        $ids = Lead::query()->eligibleForFollowUpReminderTier('day_before')->pluck('id')->all();

        $this->assertSame([$lead->id], $ids);
    }

    public function test_eligible_scope_overdue_includes_past_follow_up_dates(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $lead = Lead::factory()->assignedTo($user)->create([
            'follow_up_date' => today()->subDay(),
            'status' => 'qualified',
        ]);

        $ids = Lead::query()->eligibleForFollowUpReminderTier('overdue')->pluck('id')->all();

        $this->assertSame([$lead->id], $ids);
    }
}
