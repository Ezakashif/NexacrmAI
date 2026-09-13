<?php

namespace Tests\Unit\Models;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskReminderStateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_reminder_sent_helpers_track_tier_timestamps(): void
    {
        $task = Task::factory()->create([
            'reminders_sent' => null,
        ]);

        $this->assertFalse($task->hasReminderBeenSent('due'));
        $this->assertNull($task->reminderSentAt('due'));

        $task->markReminderSent('due');
        $task->refresh();

        $this->assertTrue($task->hasReminderBeenSent('due'));
        $this->assertNotNull($task->reminderSentAt('due'));
        $this->assertFalse($task->hasReminderBeenSent('overdue'));
    }

    public function test_changing_due_date_or_assignee_clears_reminders_sent(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['company_id' => $user->company_id]);

        $task = Task::factory()->assignedTo($user)->create([
            'due_date' => today(),
            'reminders_sent' => ['due' => now()->toIso8601String()],
        ]);

        $task->update(['due_date' => today()->addDay()]);
        $this->assertNull($task->fresh()->reminders_sent);

        $task->update([
            'due_date' => today(),
            'reminders_sent' => ['due' => now()->toIso8601String()],
        ]);
        $task->update(['assigned_to' => $other->id]);
        $this->assertNull($task->fresh()->reminders_sent);
    }

    public function test_visible_to_scope_limits_tasks_for_sales_user(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();

        $visible = Task::factory()->assignedTo($viewer)->create(['title' => 'Mine']);
        Task::factory()->assignedTo($other)->create(['title' => 'Theirs']);

        $ids = Task::visibleTo($viewer)->pluck('id')->all();

        $this->assertSame([$visible->id], $ids);
    }

    public function test_visible_to_scope_returns_all_tasks_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $sales = User::factory()->create();

        Task::factory()->assignedTo($admin)->create();
        Task::factory()->assignedTo($sales)->create();

        $this->assertSame(2, Task::visibleTo($admin)->count());
    }
}
