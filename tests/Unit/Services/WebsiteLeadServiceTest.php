<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Services\CrmNotificationDispatcher;
use App\Services\WebsiteLeadService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class WebsiteLeadServiceTest extends TestCase
{
    use RefreshDatabase;

    private WebsiteLeadService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        config([
            'website_leads.created_by_email' => null,
        ]);

        $notifications = Mockery::mock(CrmNotificationDispatcher::class);
        $notifications->shouldReceive('websiteLeadReceived')->andReturnNull();

        $this->service = new WebsiteLeadService($notifications);
    }

    public function test_create_persists_website_lead_with_defaults(): void
    {
        $admin = User::factory()->admin()->create();

        $lead = $this->service->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+1234567890',
            'company' => 'Acme Inc',
            'message' => 'Interested in your services.',
        ]);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'created_by' => $admin->id,
            'assigned_to' => null,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+1234567890',
            'company' => 'Acme Inc',
            'source' => 'website',
            'status' => 'new',
            'notes' => 'Interested in your services.',
            'company_id' => $admin->company_id,
        ]);
    }

    public function test_create_accepts_phone_only_without_email(): void
    {
        User::factory()->admin()->create();

        $lead = $this->service->create([
            'name' => 'Phone Lead',
            'phone' => '+15550100',
        ]);

        $this->assertSame('Phone Lead', $lead->name);
        $this->assertNull($lead->email);
        $this->assertSame('+15550100', $lead->phone);
    }

    public function test_create_requires_email_or_phone(): void
    {
        User::factory()->admin()->create();

        $this->expectException(ValidationException::class);

        $this->service->create([
            'name' => 'Missing Contact',
        ]);
    }

    public function test_create_logs_initial_message_as_lead_activity(): void
    {
        User::factory()->admin()->create();

        $lead = $this->service->create([
            'name' => 'Activity Lead',
            'email' => 'activity@example.com',
            'notes' => 'Prefers morning calls.',
        ]);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'note',
            'summary' => 'Prefers morning calls.',
        ]);
    }

    public function test_create_increments_sort_order_for_new_status_leads(): void
    {
        $admin = User::factory()->admin()->create();

        Lead::factory()->create([
            'company_id' => $admin->company_id,
            'created_by' => $admin->id,
            'status' => 'new',
            'sort_order' => 3,
        ]);

        $lead = $this->service->create([
            'name' => 'Sorted Lead',
            'email' => 'sorted@example.com',
        ]);

        $this->assertSame(4, $lead->sort_order);
    }

    public function test_resolve_created_by_user_id_uses_configured_email(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'owner@example.com']);
        $configured = User::factory()->create([
            'company_id' => $admin->company_id,
            'email' => 'webhook-owner@example.com',
            'status' => 'active',
        ]);

        config(['website_leads.created_by_email' => 'webhook-owner@example.com']);

        $this->assertSame($configured->id, $this->service->resolveCreatedByUserId($admin->company_id));
    }

    public function test_resolve_created_by_user_id_falls_back_to_first_active_admin(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);

        $this->assertSame($admin->id, $this->service->resolveCreatedByUserId($admin->company_id));
    }

    public function test_resolve_created_by_user_id_aborts_when_no_active_admin_exists(): void
    {
        $company = Company::factory()->create();

        $this->expectExceptionMessage('Website lead webhook has no active admin user to own new leads.');

        $this->service->resolveCreatedByUserId($company->id);
    }

    public function test_resolve_target_company_uses_configured_user_company(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'target@example.com']);

        config(['website_leads.created_by_email' => 'target@example.com']);

        $company = $this->invokeProtected($this->service, 'resolveTargetCompany');

        $this->assertTrue($company->is($admin->company));
    }

    public function test_resolve_target_company_falls_back_to_default_company(): void
    {
        $default = Company::default();
        $this->assertNotNull($default);

        $company = $this->invokeProtected($this->service, 'resolveTargetCompany');

        $this->assertTrue($company->is($default));
    }

    public function test_create_restores_previous_company_context(): void
    {
        User::factory()->admin()->create();
        $otherCompany = Company::factory()->create();

        app(\App\Support\CurrentCompany::class)->set($otherCompany);

        $this->service->create([
            'name' => 'Context Lead',
            'email' => 'context@example.com',
        ]);

        $this->assertSame($otherCompany->id, app(\App\Support\CurrentCompany::class)->id());
    }

    /**
     * @param  array<int, mixed>  $parameters
     */
    private function invokeProtected(object $object, string $method, array $parameters = []): mixed
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($object, ...$parameters);
    }
}
