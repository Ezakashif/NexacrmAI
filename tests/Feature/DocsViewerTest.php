<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocsViewerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_guest_cannot_view_docs(): void
    {
        $this->get(route('docs.index'))->assertRedirect(route('login'));
    }

    public function test_tenant_admin_can_view_docs_home_and_nested_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('docs.index'))
            ->assertOk()
            ->assertSee('NexaCRM Documentation', false)
            ->assertSee('Contents', false);

        $this->actingAs($admin)
            ->get(route('docs.show', ['path' => 'getting-started/installation']))
            ->assertOk()
            ->assertSee('Installation', false)
            ->assertSee('composer install', false);
    }

    public function test_docs_nav_shows_user_manual_section(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('docs.index'))
            ->assertOk()
            ->assertSee('User Manual', false)
            ->assertSee(route('docs.show', ['path' => 'user-manual/overview'], false), false);
    }

    public function test_contributing_policy_is_published_in_docs(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('docs.show', ['path' => 'development/contributing']))
            ->assertOk()
            ->assertSee('Development notes', false)
            ->assertSee('Codester edition of NexaCRM', false)
            ->assertSee('Tenant safety', false)
            ->assertSee('CompanyScope', false);
    }

    public function test_docs_rewrite_relative_markdown_links(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('docs.show', ['path' => 'getting-started/installation']))
            ->assertOk()
            ->assertSee(route('docs.show', ['path' => 'getting-started/configuration'], false), false);
    }

    public function test_path_traversal_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/docs/../.env')
            ->assertNotFound();

        $this->actingAs($admin)
            ->get('/docs/'.urlencode('../../composer.json'))
            ->assertNotFound();
    }

    public function test_unknown_doc_returns_not_found(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('docs.show', ['path' => 'does-not-exist']))
            ->assertNotFound();
    }

    public function test_super_admin_can_view_docs(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('docs.index'))
            ->assertOk()
            ->assertSee('Documentation', false);
    }

    public function test_docs_page_shows_pdf_download_links(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('docs.index'))
            ->assertOk()
            ->assertSee(route('docs.pdf', [], false), false)
            ->assertSee(route('docs.pdf.page', ['path' => 'README'], false), false)
            ->assertSee('Download all', false);
    }

    public function test_guest_cannot_download_docs_pdf(): void
    {
        $this->get(route('docs.pdf'))->assertRedirect(route('login'));
        $this->get(route('docs.pdf.page', ['path' => 'README']))->assertRedirect(route('login'));
    }

    public function test_tenant_can_download_single_page_pdf(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('docs.pdf.page', ['path' => 'user-manual/overview']));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'crm-docs-user-manual-overview-',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_tenant_can_download_full_docs_pdf(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('docs.pdf'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'crm-documentation-',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_unknown_doc_pdf_returns_not_found(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('docs.pdf.page', ['path' => 'does-not-exist']))
            ->assertNotFound();
    }
}
