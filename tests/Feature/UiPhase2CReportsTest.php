<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiPhase2CReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_reports_page_uses_shared_header_and_section_polish(): void
    {
        $user = User::factory()->admin()->create();

        Lead::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'status' => 'new',
            'source' => 'website',
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('crm-page-header', false)
            ->assertSee('Reports')
            ->assertSee('Sales Performance')
            ->assertSee('crm-section-heading', false)
            ->assertSee('Export CSV');
    }
}
