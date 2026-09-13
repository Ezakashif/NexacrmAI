<?php

namespace Tests\Feature;

use App\Models\DemoVisit;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoVisitSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_super_admin_can_view_demo_visits_index_and_show(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $visit = DemoVisit::query()->create([
            'email' => 'alex@example.com',
            'name' => 'Alex Morgan',
            'company' => 'Northline',
            'country' => 'PK',
            'persona' => 'admin',
            'contact_consent' => true,
            'is_anonymous' => false,
            'status' => DemoVisit::STATUS_NEW,
            'started_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('superadmin.demo-visits.index'))
            ->assertOk()
            ->assertSee('Demo visits', false)
            ->assertSee('alex@example.com', false)
            ->assertSee('Alex Morgan', false)
            ->assertSee('Pakistan', false);

        $this->actingAs($superAdmin)
            ->get(route('superadmin.demo-visits.show', $visit))
            ->assertOk()
            ->assertSee('alex@example.com', false)
            ->assertSee('Northline', false)
            ->assertSee('Pakistan', false)
            ->assertSee('PK', false);

        $this->assertSame(DemoVisit::STATUS_REVIEWED, $visit->fresh()->status);
    }

    public function test_super_admin_can_update_demo_visit_status(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $visit = DemoVisit::query()->create([
            'email' => 'jordan@example.com',
            'persona' => 'sales',
            'contact_consent' => false,
            'is_anonymous' => false,
            'status' => DemoVisit::STATUS_NEW,
            'started_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('superadmin.demo-visits.status', $visit), [
                'status' => DemoVisit::STATUS_CLOSED,
            ])
            ->assertRedirect();

        $visit->refresh();
        $this->assertSame(DemoVisit::STATUS_CLOSED, $visit->status);
        $this->assertSame($superAdmin->id, $visit->reviewed_by);
        $this->assertNotNull($visit->reviewed_at);
    }

    public function test_tenant_admin_cannot_access_demo_visits(): void
    {
        $admin = User::factory()->admin()->create();

        DemoVisit::query()->create([
            'email' => 'hidden@example.com',
            'persona' => 'admin',
            'is_anonymous' => false,
            'status' => DemoVisit::STATUS_NEW,
            'started_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('superadmin.demo-visits.index'))
            ->assertForbidden();
    }
}
