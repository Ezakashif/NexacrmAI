<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTourTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_guest_cannot_complete_or_restart_tour(): void
    {
        $this->postJson(route('dashboard.tour.complete'))->assertUnauthorized();
        $this->postJson(route('dashboard.tour.restart'))->assertUnauthorized();
    }

    public function test_dashboard_auto_starts_tour_for_users_who_have_not_completed_it(): void
    {
        $admin = User::factory()->admin()->create([
            'dashboard_tour_completed_at' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Replay tour', false)
            ->assertSee('DashboardTourBoot', false)
            ->assertSee('"autoStart":true', false)
            ->assertSee('dashboard-tour.js', false);
    }

    public function test_dashboard_does_not_auto_start_tour_after_completion(): void
    {
        $admin = User::factory()->admin()->create([
            'dashboard_tour_completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Replay tour', false)
            ->assertSee('"autoStart":false', false);
    }

    public function test_user_can_complete_dashboard_tour(): void
    {
        $admin = User::factory()->admin()->create([
            'dashboard_tour_completed_at' => null,
        ]);

        $this->actingAs($admin)
            ->postJson(route('dashboard.tour.complete'))
            ->assertOk()
            ->assertJson(['completed' => true]);

        $this->assertNotNull($admin->fresh()->dashboard_tour_completed_at);
    }

    public function test_user_can_restart_dashboard_tour(): void
    {
        $admin = User::factory()->admin()->create([
            'dashboard_tour_completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->postJson(route('dashboard.tour.restart'))
            ->assertOk()
            ->assertJson([
                'completed' => false,
                'restarted' => true,
            ]);

        $this->assertNull($admin->fresh()->dashboard_tour_completed_at);
    }

    public function test_tour_steps_respect_permissions(): void
    {
        $sales = User::factory()->create([
            'role' => 'user',
            'dashboard_tour_completed_at' => null,
        ]);

        $response = $this->actingAs($sales)
            ->get(route('dashboard'))
            ->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('Welcome to your dashboard', $content);
        $this->assertStringContainsString('"id":"leads"', $content);
    }
}
