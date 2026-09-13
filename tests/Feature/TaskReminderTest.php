<?php

namespace Tests\Feature;

use App\Jobs\SendTaskReminderJob;
use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskDue;
use App\Services\UserNotificationPreferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TaskReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_notifies_the_assignee_when_a_task_is_due_today(): void
    {
        Notification::fake();

        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create([
            'company_id' => $user->company_id,
            'assigned_to' => $user->id,
            'status' => 'pending',
            'due_date' => today(),
        ]);

        $this->artisan('tasks:send-reminders --tier=due')->assertSuccessful();

        Notification::assertSentTo($user, TaskDue::class, fn (TaskDue $notification) => $notification->task->is($task)
            && $notification->tier === 'due');
        $this->assertTrue($task->fresh()->hasReminderBeenSent('due'));
    }

    public function test_overdue_reminders_are_deduplicated_until_the_repeat_interval_has_elapsed(): void
    {
        Notification::fake();
        config(['task_reminders.tiers.overdue.repeat_days' => 1]);

        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create([
            'company_id' => $user->company_id,
            'assigned_to' => $user->id,
            'status' => 'in_progress',
            'due_date' => today()->subDay(),
        ]);

        $this->artisan('tasks:send-reminders --tier=overdue')->assertSuccessful();
        $this->artisan('tasks:send-reminders --tier=overdue')->assertSuccessful();

        Notification::assertSentTo($user, TaskDue::class, 1);
        $this->travel(1)->days();
        $this->artisan('tasks:send-reminders --tier=overdue')->assertSuccessful();
        Notification::assertSentTo($user, TaskDue::class, 2);
    }

    public function test_task_reminders_do_not_cross_company_boundaries(): void
    {
        Notification::fake();

        $company = Company::factory()->create();
        $foreignUser = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $task = Task::factory()->create([
            'assigned_to' => $foreignUser->id,
            'status' => 'pending',
            'due_date' => today(),
        ]);

        $this->artisan('tasks:send-reminders --tier=due')->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertFalse($task->fresh()->hasReminderBeenSent('due'));
    }

    public function test_opted_out_assignee_does_not_receive_task_reminders(): void
    {
        Notification::fake();

        $user = User::factory()->create(['status' => 'active']);
        app(UserNotificationPreferenceService::class)->update($user, [
            'task_due' => ['database' => false, 'email' => false],
        ]);
        Task::factory()->create([
            'company_id' => $user->company_id,
            'assigned_to' => $user->id,
            'status' => 'pending',
            'due_date' => today(),
        ]);

        $this->artisan('tasks:send-reminders --tier=due')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_command_queues_jobs_when_the_queue_is_asynchronous(): void
    {
        Queue::fake();

        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->create([
            'company_id' => $user->company_id,
            'assigned_to' => $user->id,
            'status' => 'pending',
            'due_date' => today(),
        ]);

        $this->artisan('tasks:send-reminders --tier=due')->assertSuccessful();

        Queue::assertPushed(SendTaskReminderJob::class, 1);
    }
}
