<?php

namespace Tests\Unit\Services\Analytics;

use App\Models\Company;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Services\Analytics\CrmAnalytics;
use Carbon\Carbon;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_apply_overdue_tasks_excludes_completed_and_cancelled(): void
    {
        $user = User::factory()->create();

        Task::factory()->assignedTo($user)->create([
            'status' => 'pending',
            'due_date' => now()->subDay(),
        ]);
        Task::factory()->assignedTo($user)->create([
            'status' => 'in_progress',
            'due_date' => now()->subDays(2),
        ]);
        Task::factory()->assignedTo($user)->create([
            'status' => 'completed',
            'due_date' => now()->subDay(),
        ]);
        Task::factory()->assignedTo($user)->create([
            'status' => 'cancelled',
            'due_date' => now()->subDay(),
        ]);
        Task::factory()->assignedTo($user)->create([
            'status' => 'pending',
            'due_date' => now()->addDay(),
        ]);
        Task::factory()->assignedTo($user)->create([
            'status' => 'pending',
            'due_date' => null,
        ]);

        $count = CrmAnalytics::applyOverdueTasks(Task::query())->count();

        $this->assertSame(2, $count);
    }

    public function test_monthly_lead_growth_returns_zero_filled_months(): void
    {
        Carbon::setTestNow('2026-07-15 12:00:00');

        $user = User::factory()->create();

        Lead::factory()->assignedTo($user)->createdAt('2026-05-10')->create([
            'created_by' => $user->id,
        ]);
        Lead::factory()->assignedTo($user)->createdAt('2026-07-03')->create([
            'created_by' => $user->id,
        ]);
        Lead::factory()->assignedTo($user)->createdAt('2026-07-20')->create([
            'created_by' => $user->id,
        ]);
        Lead::factory()->assignedTo($user)->createdAt('2026-04-01')->create([
            'created_by' => $user->id,
        ]);

        $result = CrmAnalytics::monthlyLeadGrowth(Lead::query(), 3);

        $this->assertSame(['May 2026', 'Jun 2026', 'Jul 2026'], $result['labels']);
        $this->assertSame([1, 0, 2], $result['data']);

        Carbon::setTestNow();
    }

    public function test_lead_source_distribution_orders_known_sources_and_groups_other(): void
    {
        $user = User::factory()->create();

        Lead::factory()->assignedTo($user)->count(2)->create([
            'created_by' => $user->id,
            'source' => 'website',
        ]);
        Lead::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'source' => 'referral',
        ]);
        Lead::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'source' => null,
        ]);
        Lead::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'source' => 'trade_show',
        ]);

        $result = CrmAnalytics::leadSourceDistribution(Lead::query());

        $this->assertSame('Website', $result['labels'][0]);
        $this->assertSame(2, $result['data'][0]);
        $this->assertSame('Referral', $result['labels'][array_search('Referral', $result['labels'], true)]);
        $this->assertContains('Other / Unspecified', $result['labels']);

        $otherIndex = array_search('Other / Unspecified', $result['labels'], true);
        $this->assertSame(2, $result['data'][$otherIndex]);
    }

    public function test_month_expression_uses_sqlite_strftime(): void
    {
        $expression = CrmAnalytics::monthExpression('created_at', Lead::query());

        $this->assertSame("strftime('%Y-%m', created_at)", $expression);
    }

    public function test_date_expression_uses_sqlite_strftime(): void
    {
        $expression = CrmAnalytics::dateExpression('due_date', Task::query());

        $this->assertSame("strftime('%Y-%m-%d', due_date)", $expression);
    }

    public function test_monthly_lead_growth_respects_scoped_query(): void
    {
        Carbon::setTestNow('2026-07-15 12:00:00');

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $userB = User::factory()->create(['company_id' => $companyB->id]);

        Lead::factory()->assignedTo($userA)->createdAt('2026-07-01')->create([
            'created_by' => $userA->id,
        ]);
        Lead::factory()->assignedTo($userB)->createdAt('2026-07-02')->create([
            'created_by' => $userB->id,
        ]);

        $result = CrmAnalytics::monthlyLeadGrowth(
            Lead::query()->where('company_id', $companyA->id),
            1,
        );

        $this->assertSame(['Jul 2026'], $result['labels']);
        $this->assertSame([1], $result['data']);

        Carbon::setTestNow();
    }
}
