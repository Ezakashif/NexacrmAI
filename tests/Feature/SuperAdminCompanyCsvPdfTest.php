<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SuperAdminCompanyCsvPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_super_admin_can_export_companies_csv(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        Company::factory()->create(['name' => 'Export Co', 'slug' => 'export-co']);

        $response = $this->actingAs($superAdmin)
            ->get(route('superadmin.companies.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Name,Slug,Owner,"Owner Email",Plan,Status', str_replace("\xEF\xBB\xBF", '', $content));
        $this->assertStringContainsString('Export Co', $content);
    }

    public function test_super_admin_can_export_companies_pdf_list(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.companies.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_super_admin_can_export_single_company_pdf(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::default();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.companies.pdf', $company))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_super_admin_can_download_company_import_sample(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)
            ->get(route('superadmin.companies.import.sample'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    public function test_super_admin_can_import_companies_csv(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $csv = UploadedFile::fake()->createWithContent(
            'companies.csv',
            implode("\n", [
                'name,slug,status,admin_name,admin_email,admin_password',
                'Imported Co,imported-co,active,Import Admin,import-admin@example.com,Password123!',
            ])
        );

        $this->actingAs($superAdmin)
            ->post(route('superadmin.companies.import.store'), [
                'csv_file' => $csv,
            ])
            ->assertRedirect(route('superadmin.companies.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('companies', [
            'slug' => 'imported-co',
            'name' => 'Imported Co',
        ]);

        $admin = User::withoutCompanyScope()->where('email', 'import-admin@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_tenant_admin_cannot_export_companies(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('superadmin.companies.export'))
            ->assertForbidden();
    }
}
