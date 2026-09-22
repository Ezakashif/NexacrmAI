<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Permission;
use App\Models\User;
use App\Services\Ai\LeadAssistService;
use App\Services\Ai\NullAiClient;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadAiAssistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_suggest_returns_503_when_ai_is_disabled(): void
    {
        config(['ai.enabled' => false]);
        $this->app->instance(LeadAssistService::class, new LeadAssistService(new NullAiClient));

        $user = User::factory()->admin()->create();
        $lead = Lead::factory()->create([
            'company_id' => $user->company_id,
            'assigned_to' => $user->id,
        ]);

        $this->actingAs($user)
            ->postJson(route('leads.ai.suggest', $lead))
            ->assertStatus(503)
            ->assertJsonPath('ok', false);
    }

    public function test_suggest_is_forbidden_without_ai_permission(): void
    {
        $user = User::factory()->create();
        $salesRole = $user->roles()->where('slug', 'sales')->firstOrFail();
        $permissionId = Permission::query()->where('slug', 'ai_assist.leads')->value('id');
        $this->assertNotNull($permissionId);
        $salesRole->permissions()->detach($permissionId);

        $lead = Lead::factory()->create([
            'company_id' => $user->company_id,
            'assigned_to' => $user->id,
        ]);

        $this->actingAs($user->fresh())
            ->postJson(route('leads.ai.suggest', $lead))
            ->assertForbidden();
    }
}
