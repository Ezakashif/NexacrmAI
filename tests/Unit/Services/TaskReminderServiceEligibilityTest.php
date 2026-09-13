<?php

namespace Tests\Unit\Services;

use App\Models\Task;
use App\Models\User;
use App\Services\TaskReminderService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class TaskReminderServiceEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private TaskReminderService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        config(['task_reminders.tiers.overdue.repeat_days' => 1]);

        $this->service = app(TaskReminderService::class);
    }

    /**
     * @dataProvider ineligibleTaskProvider
     */
    public function test_is_eligible_returns_false_for_ineligible_tasks(
        string $tier,
        callable $taskFactory,
    ): void {
        $task = $taskFactory($this);

        $this->assertFalse($this->service->isEligible($task, $tier));
    }

    public static function ineligibleTaskProvider(): array
    {
        return [
            'due without assignee' => [
                'due',
                fn (self $test) => $test->makeTask(['assigned_to' => null, 'due_date' => today()]),
            ],
            'due without due_date' => [
                'due',
                fn (self $test) => $test->makeTask(['due_date' => null]),
            ],
            'due completed task' => [
                'due',
                fn (self $test) => $test->makeTask(['status' => 'completed', 'due_date' => today()]),
            ],
            'due cancelled task' => [
                'due',
                fn (self $test) => $test->makeTask(['status' => 'cancelled', 'due_date' => today()]),
            ],
            'due already sent' => [
                'due',
                fn (self $test) => $test->makeTask([
                    'due_date' => today(),
                    'reminders_sent' => ['due' => now()->toIso8601String()],
                ]),
            ],
            'due wrong day' => [
                'due',
                fn (self $test) => $test->makeTask(['due_date' => today()->addDay()]),
            ],
            'overdue future due date' => [
                'overdue',
                fn (self $test) => $test->makeTask(['due_date' => today()]),
            ],
            'overdue before repeat interval' => [
                'overdue',
                fn (self $test) => $test->makeTask([
                    'due_date' => today()->subDays(2),
                    'reminders_sent' => ['overdue' => now()->subHours(2)->toIso8601String()],
                ]),
            ],
            'unknown tier' => [
                'bogus',
                fn (self $test) => $test->makeTask(['due_date' => today()]),
            ],
        ];
    }

    /**
     * @dataProvider eligibleTaskProvider
     */
    public function test_is_eligible_returns_true_for_matching_tiers(
        string $tier,
        callable $taskFactory,
    ): void {
        $task = $taskFactory($this);

        $this->assertTrue($this->service->isEligible($task, $tier));
    }

    public static function eligibleTaskProvider(): array
    {
        return [
            'due today pending' => [
                'due',
                fn (self $test) => $test->makeTask(['status' => 'pending', 'due_date' => today()]),
            ],
            'due today in_progress' => [
                'due',
                fn (self $test) => $test->makeTask(['status' => 'in_progress', 'due_date' => today()]),
            ],
            'overdue first reminder' => [
                'overdue',
                fn (self $test) => $test->makeTask(['due_date' => today()->subDay()]),
            ],
            'overdue after repeat interval' => [
                'overdue',
                fn (self $test) => $test->makeTask([
                    'due_date' => today()->subDays(2),
                    'reminders_sent' => ['overdue' => now()->subDay()->toIso8601String()],
                ]),
            ],
        ];
    }

    public function test_assert_valid_tier_rejects_unknown_tier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown task reminder tier [bogus].');

        $reflection = new \ReflectionMethod($this->service, 'assertValidTier');
        $reflection->setAccessible(true);
        $reflection->invoke($this->service, 'bogus');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeTask(array $overrides = []): Task
    {
        $user = User::factory()->create(['status' => 'active']);

        return Task::factory()->assignedTo($user)->make(array_merge([
            'status' => 'pending',
            'reminders_sent' => null,
        ], $overrides));
    }
}
