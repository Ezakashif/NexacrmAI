<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\SuperAdmin\ImpersonationService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionExpiryEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_expired_subscription_blocks_crm_access(): void
    {
        $company = Company::factory()->expired()->create();
        $user = User::factory()->admin()->create(['company_id' => $company->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Your free trial has expired.',
            ]);

        $this->assertGuest();
    }

    public function test_past_trial_blocks_crm_access_and_marks_expired(): void
    {
        $company = Company::factory()->create([
            'subscription_status' => Company::SUBSCRIPTION_TRIAL,
            'trial_ends_at' => now()->subDay(),
        ]);
        $user = User::factory()->admin()->create(['company_id' => $company->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Your free trial has expired.',
            ]);

        $this->assertSame(Company::SUBSCRIPTION_EXPIRED, $company->fresh()->subscription_status);
        $this->assertGuest();
    }

    public function test_login_shows_free_trial_expired_message(): void
    {
        $company = Company::factory()->create([
            'subscription_status' => Company::SUBSCRIPTION_TRIAL,
            'trial_ends_at' => now()->subDay(),
            'slug' => 'trial-expired-co',
        ]);
        User::factory()->admin()->create([
            'company_id' => $company->id,
            'email' => 'trial-expired@example.com',
        ]);

        $this->post('/login', [
            'email' => 'trial-expired@example.com',
            'password' => 'password',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Your free trial has expired.',
            ]);

        $this->assertGuest();
    }

    public function test_login_rejects_expired_subscription(): void
    {
        $company = Company::factory()->expired()->create(['slug' => 'expired-co']);
        $user = User::factory()->admin()->create([
            'company_id' => $company->id,
            'email' => 'expired@example.com',
        ]);

        $this->post('/login', [
            'email' => 'expired@example.com',
            'password' => 'password',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Your free trial has expired.',
            ]);

        $this->assertGuest();
    }

    public function test_impersonation_can_access_expired_company(): void
    {
        $company = Company::factory()->expired()->create();
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->withSession([
                ImpersonationService::SESSION_IMPERSONATOR_ID => $superAdmin->id,
                ImpersonationService::SESSION_IMPERSONATION_LOG_ID => 1,
            ]);

        // Simulate an active impersonation session as the tenant admin.
        $this->actingAs($admin)
            ->withSession([
                ImpersonationService::SESSION_IMPERSONATOR_ID => $superAdmin->id,
                ImpersonationService::SESSION_IMPERSONATION_LOG_ID => 1,
            ])
            ->get(route('dashboard'))
            ->assertOk();
    }
}
