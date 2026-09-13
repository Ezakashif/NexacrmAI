<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteLeadDemoTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'demo-test-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['website_leads.webhook_secret' => self::SECRET]);
    }

    public function test_admin_can_view_demo_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('demo.website-lead'));

        $response->assertOk()
            ->assertSee('Simulated website contact form');
    }

    public function test_non_admin_cannot_view_demo_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('demo.website-lead'));

        $response->assertForbidden();
    }

    public function test_admin_can_submit_demo_form_through_webhook(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson(route('demo.website-lead.store'), [
            'name' => 'Demo Visitor',
            'email' => 'visitor@example.com',
            'message' => 'Testing from admin demo form',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['lead_id', 'message']);

        $this->assertDatabaseHas('leads', [
            'name' => 'Demo Visitor',
            'email' => 'visitor@example.com',
            'source' => 'website',
        ]);

        $this->assertSame(1, Lead::count());
    }
}
