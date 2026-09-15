<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\SuperAdmin\CompanySoftDeleteService;
use App\Services\SuperAdmin\PlatformSearchService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SoftDeletedCompanyCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_soft_deleted_company_and_users_are_hidden_from_platform_search(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create([
            'name' => 'Northwind Cleanup',
            'email' => 'admin@cleanup.nexacrm.test',
            'slug' => 'northwind-cleanup',
        ]);
        $admin = User::factory()->admin()->create([
            'company_id' => $company->id,
            'name' => 'Cleanup Owner',
            'email' => 'admin@cleanup.nexacrm.test',
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('superadmin.companies.destroy', $company))
            ->assertRedirect(route('superadmin.companies.index'));

        $this->assertSoftDeleted($company);
        $this->assertNotSame('admin@cleanup.nexacrm.test', $admin->fresh()->email);
        $this->assertSame('inactive', $admin->fresh()->status);

        $results = app(PlatformSearchService::class)->search('Northwind');
        $this->assertTrue($results['companies']->isEmpty());
        $this->assertTrue($results['users']->isEmpty());

        $this->actingAs($superAdmin)
            ->getJson(route('superadmin.search.suggest', ['q' => 'Northwind']))
            ->assertOk()
            ->assertJsonPath('companies', [])
            ->assertJsonPath('users', []);
    }

    public function test_admin_email_from_soft_deleted_company_can_be_reused_when_creating_company(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create([
            'name' => 'Northwind Cleanup',
            'slug' => 'northwind-cleanup',
            'email' => 'admin@cleanup.nexacrm.test',
        ]);
        User::factory()->admin()->create([
            'company_id' => $company->id,
            'email' => 'admin@cleanup.nexacrm.test',
            'password' => Hash::make('Password1!'),
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('superadmin.companies.destroy', $company))
            ->assertRedirect();

        $this->actingAs($superAdmin)
            ->post(route('superadmin.companies.store'), [
                'name' => 'Northwind Cleanup',
                'slug' => 'northwind-cleanup',
                'email' => 'admin@cleanup.nexacrm.test',
                'status' => 'active',
                'subscription_status' => 'trial',
                'admin_name' => 'Cleanup Owner',
                'admin_email' => 'admin@cleanup.nexacrm.test',
                'admin_password' => 'Password1!x',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('companies', [
            'name' => 'Northwind Cleanup',
            'slug' => 'northwind-cleanup',
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@cleanup.nexacrm.test',
            'status' => 'active',
        ]);
    }

    public function test_legacy_soft_deleted_admin_email_is_freed_on_create_attempt(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create([
            'name' => 'Northwind Cleanup',
            'slug' => 'northwind-cleanup',
            'email' => 'admin@cleanup.nexacrm.test',
        ]);
        $admin = User::factory()->admin()->create([
            'company_id' => $company->id,
            'email' => 'admin@cleanup.nexacrm.test',
            'password' => Hash::make('Password1!'),
        ]);

        // Simulate a soft-delete from before identifier archiving existed.
        $company->delete();
        $this->assertSame('admin@cleanup.nexacrm.test', $admin->fresh()->email);
        $this->assertSame('northwind-cleanup', $company->fresh()->slug);

        // Search hides orphaned users, which previously looked like the email was free.
        $this->actingAs($superAdmin)
            ->get(route('superadmin.search.index', ['q' => 'admin@cleanup.nexacrm.test']))
            ->assertOk()
            ->assertSee('No users matched', false)
            ->assertSee('No companies matched', false);

        $this->actingAs($superAdmin)
            ->post(route('superadmin.companies.store'), [
                'name' => 'Northwind Cleanup',
                'slug' => 'northwind-cleanup',
                'email' => 'admin@cleanup.nexacrm.test',
                'status' => 'active',
                'subscription_status' => 'trial',
                'admin_name' => 'Cleanup Owner',
                'admin_email' => 'admin@cleanup.nexacrm.test',
                'admin_password' => 'Password1!x',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertNotSame('admin@cleanup.nexacrm.test', $admin->fresh()->email);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@cleanup.nexacrm.test',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('companies', [
            'name' => 'Northwind Cleanup',
            'slug' => 'northwind-cleanup',
            'deleted_at' => null,
        ]);
    }

    public function test_restoring_company_restores_slug_and_user_email(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create([
            'name' => 'Restore Me',
            'slug' => 'restore-me',
        ]);
        $admin = User::factory()->admin()->create([
            'company_id' => $company->id,
            'email' => 'owner@restore.test',
        ]);

        app(CompanySoftDeleteService::class)->softDelete($company);

        $company->refresh();
        $admin->refresh();
        $this->assertNotNull($company->deleted_at);
        $this->assertStringContainsString('-deleted-'.$company->id, $company->slug);
        $this->assertNotSame('owner@restore.test', $admin->email);

        $this->actingAs($superAdmin)
            ->post(route('superadmin.companies.restore', $company->id))
            ->assertRedirect(route('superadmin.companies.show', $company->id));

        $company->refresh();
        $admin->refresh();
        $this->assertNull($company->deleted_at);
        $this->assertSame('restore-me', $company->slug);
        $this->assertSame('owner@restore.test', $admin->email);
        $this->assertSame('active', $admin->status);
    }

    public function test_release_identifiers_repairs_legacy_soft_deleted_companies(): void
    {
        $company = Company::factory()->create([
            'name' => 'Legacy Deleted',
            'slug' => 'legacy-deleted',
        ]);
        $admin = User::factory()->admin()->create([
            'company_id' => $company->id,
            'email' => 'legacy@example.com',
        ]);

        // Simulate pre-fix soft delete that left identifiers reserved.
        $company->delete();

        $this->assertSame('legacy-deleted', $company->fresh()->slug);
        $this->assertSame('legacy@example.com', $admin->fresh()->email);

        $repaired = app(CompanySoftDeleteService::class)->releaseIdentifiersForTrashedCompanies();
        $this->assertGreaterThanOrEqual(1, $repaired);

        $this->assertStringContainsString('-deleted-'.$company->id, $company->fresh()->slug);
        $this->assertNotSame('legacy@example.com', $admin->fresh()->email);
    }
}
