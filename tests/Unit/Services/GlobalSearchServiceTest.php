<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Services\GlobalSearchService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    private GlobalSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->service = app(GlobalSearchService::class);
    }

    public function test_search_marks_short_terms_as_too_short_and_returns_empty_results(): void
    {
        $user = User::factory()->create();

        Lead::factory()->assignedTo($user)->create([
            'name' => 'Acme Lead',
            'company' => 'Acme Robotics',
        ]);

        $payload = $this->service->search($user, 'a');

        $this->assertTrue($payload['too_short']);
        $this->assertSame(0, $payload['total']);
        $this->assertTrue($payload['leads']->isEmpty());
        $this->assertTrue($payload['customers']->isEmpty());
    }

    public function test_search_returns_empty_when_user_has_no_view_permissions(): void
    {
        $user = User::factory()->create();
        $restricted = Role::query()->create([
            'company_id' => $user->company_id,
            'slug' => 'search-none',
            'name' => 'Search None',
            'description' => 'No search permissions.',
            'is_system' => false,
        ]);
        $user->syncRoles([$restricted->id]);

        Lead::factory()->create(['name' => 'Hidden Lead']);

        $payload = $this->service->search($user->fresh(), 'Hidden');

        $this->assertFalse($payload['too_short']);
        $this->assertFalse($payload['can_view_leads']);
        $this->assertSame(0, $payload['total']);
    }

    public function test_sales_user_only_sees_assigned_leads_and_tasks(): void
    {
        $viewer = User::factory()->create(['name' => 'Viewer Sales']);
        $other = User::factory()->create(['name' => 'Other Sales']);

        Lead::factory()->assignedTo($viewer)->create([
            'name' => 'Alpha Lead',
            'company' => 'Acme Robotics',
        ]);
        Lead::factory()->assignedTo($other)->create([
            'name' => 'Hidden Lead',
            'company' => 'Acme Robotics',
        ]);

        Task::factory()->assignedTo($viewer)->create([
            'title' => 'Viewer Task Alpha',
        ]);
        Task::factory()->assignedTo($other)->create([
            'title' => 'Hidden Task Alpha',
        ]);

        Customer::factory()->create([
            'name' => 'Beta Customer',
            'company_name' => 'Acme Robotics',
        ]);

        $payload = $this->service->search($viewer, 'Alpha');

        $this->assertSame(1, $payload['leads']->count());
        $this->assertSame('Alpha Lead', $payload['leads']->first()->name);
        $this->assertSame(1, $payload['tasks']->count());
        $this->assertSame('Viewer Task Alpha', $payload['tasks']->first()->title);
        $this->assertGreaterThanOrEqual(2, $payload['total']);
    }

    public function test_suggest_builds_grouped_payload_for_matching_records(): void
    {
        $user = User::factory()->create();

        Lead::factory()->assignedTo($user)->create([
            'name' => 'Suggest Lead',
            'email' => 'suggest@example.com',
        ]);

        $payload = $this->service->suggest($user, 'Suggest');

        $this->assertFalse($payload['too_short']);
        $this->assertNotEmpty($payload['groups']);
        $this->assertSame('leads', $payload['groups'][0]['type']);
        $this->assertSame('Suggest Lead', $payload['groups'][0]['items'][0]['title']);
    }

    public function test_trimmed_empty_term_is_treated_as_too_short(): void
    {
        $user = User::factory()->create();

        $payload = $this->service->search($user, '   ');

        $this->assertTrue($payload['too_short']);
        $this->assertSame('', $payload['term']);
    }
}
