<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiPhase2BSharedShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_pages_include_shared_ui_shell(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('id="crm-toast-stack"', false);
        $response->assertSee('id="crm-confirm-backdrop"', false);
        $response->assertSee('id="crm-flash-data"', false);
        $response->assertSee('js/crm-ui.js', false);
        $response->assertSee('js/password-toggle.js', false);
        $response->assertSee('css/password-field.css', false);
        $response->assertSee('css/crm-tokens.css', false);
        $response->assertSee('css/crm-app.css', false);
    }

    public function test_success_flash_is_exposed_to_toast_shell(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email_verified_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['success' => 'Lead created successfully.'])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Lead created successfully.', false);
        $response->assertSee('crm-flash-data', false);
    }

    public function test_shared_blade_components_render(): void
    {
        $html = view('components.page-header', [
            'title' => 'Customers',
            'subtitle' => 'Manage your accounts.',
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => '/dashboard'],
                ['label' => 'Customers'],
            ],
        ])->render();

        $this->assertStringContainsString('crm-page-header', $html);
        $this->assertStringContainsString('Customers', $html);
        $this->assertStringContainsString('Manage your accounts.', $html);
        $this->assertStringContainsString('breadcrumb', $html);

        $empty = view('components.empty-state', [
            'title' => 'No customers yet',
            'description' => 'Create your first customer to get started.',
            'actionUrl' => '/customers/create',
            'actionLabel' => 'Add Customer',
        ])->render();

        $this->assertStringContainsString('crm-empty', $empty);
        $this->assertStringContainsString('No customers yet', $empty);
        $this->assertStringContainsString('Add Customer', $empty);

        $skeleton = view('components.skeleton', [
            'variant' => 'card',
        ])->render();

        $this->assertStringContainsString('crm-skeleton--card', $skeleton);
    }
}
