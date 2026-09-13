<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteLeadWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'website_leads.webhook_secret' => self::SECRET,
            'website_leads.created_by_email' => null,
        ]);
    }

    public function test_it_creates_a_website_lead_with_valid_secret(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->postJson(route('webhooks.leads.website'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+1234567890',
            'company' => 'Acme Inc',
            'message' => 'Interested in your services.',
        ], [
            'Authorization' => 'Bearer '.self::SECRET,
        ]);

        $response->assertCreated()
            ->assertJson([
                'message' => 'Lead created.',
            ]);

        $this->assertDatabaseHas('leads', [
            'id' => $response->json('lead_id'),
            'created_by' => $admin->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+1234567890',
            'company' => 'Acme Inc',
            'source' => 'website',
            'status' => 'new',
            'notes' => 'Interested in your services.',
        ]);
    }

    public function test_it_accepts_secret_from_header(): void
    {
        User::factory()->admin()->create();

        $response = $this->postJson(route('webhooks.leads.website'), [
            'name' => 'John Smith',
            'email' => 'john@example.com',
        ], [
            'X-Webhook-Secret' => self::SECRET,
        ]);

        $response->assertCreated();
        $this->assertSame(1, Lead::count());
    }

    public function test_it_rejects_invalid_secret(): void
    {
        User::factory()->admin()->create();

        $response = $this->postJson(route('webhooks.leads.website'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ], [
            'Authorization' => 'Bearer wrong-secret',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_it_rejects_requests_when_secret_is_not_configured(): void
    {
        config(['website_leads.webhook_secret' => null]);

        User::factory()->admin()->create();

        $response = $this->postJson(route('webhooks.leads.website'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ], [
            'Authorization' => 'Bearer '.self::SECRET,
        ]);

        $response->assertStatus(503);
    }

    public function test_it_validates_required_fields(): void
    {
        User::factory()->admin()->create();

        $response = $this->postJson(route('webhooks.leads.website'), [], [
            'Authorization' => 'Bearer '.self::SECRET,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'phone']);
    }
}
