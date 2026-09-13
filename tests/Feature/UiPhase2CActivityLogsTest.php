<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiPhase2CActivityLogsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_activity_logs_index_uses_shared_header_and_empty_state(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSee('crm-page-header', false)
            ->assertSee('Activity Log')
            ->assertSee('No activity recorded yet')
            ->assertSee('crm-empty', false);
    }

    public function test_activity_logs_filter_empty_state_offers_clear(): void
    {
        $admin = User::factory()->admin()->create();

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'lead.created',
            'properties' => ['name' => 'Sample'],
        ]);

        $this->actingAs($admin)
            ->get(route('activity-logs.index', ['action' => 'user.deleted']))
            ->assertOk()
            ->assertSee('No activity matches your filters')
            ->assertSee('Clear filters');
    }

    public function test_activity_logs_list_uses_shared_filter_card(): void
    {
        $admin = User::factory()->admin()->create();

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'lead.created',
            'properties' => ['name' => 'Sample Lead'],
        ]);

        $this->actingAs($admin)
            ->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSee('crm-filter-card', false)
            ->assertSee('Sample Lead')
            ->assertSee('activity-log-row', false);
    }
}
