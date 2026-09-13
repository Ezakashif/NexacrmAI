<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Scopes\CompanyScope;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\SeedsLiveDemo;
use Tests\TestCase;

class LiveDemoSecurityTest extends TestCase
{
    use RefreshDatabase;
    use SeedsLiveDemo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLiveDemo();
    }

    public function test_demo_credentials_are_not_exposed_in_public_html(): void
    {
        $password = DemoDataSeeder::demoPassword();

        foreach ([
            route('marketing.home'),
            route('login'),
            route('demo.select'),
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertDontSee($password, false)
                ->assertDontSee('DEMO_SEED_PASSWORD', false)
                ->assertDontSee('Demo-Northstar-2026!', false);
        }

        $this->assertStringNotContainsString(
            'Demo-Northstar-2026!',
            file_get_contents(base_path('database/seeders/DemoDataSeeder.php')),
        );
    }

    public function test_lead_xss_is_escaped_in_demo_workspace(): void
    {
        $admin = $this->demoUser('admin');
        $payload = '<script>alert("xss")</script>';

        $this->actingAs($admin)
            ->post(route('leads.store'), [
                'name' => $payload,
                'status' => 'new',
            ])
            ->assertRedirect();

        $lead = Lead::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $admin->company_id)
            ->where('name', $payload)
            ->firstOrFail();

        $this->actingAs($admin)
            ->get(route('leads.show', $lead))
            ->assertOk()
            ->assertDontSee($payload, false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_search_sql_injection_does_not_error_or_leak_other_tenants(): void
    {
        $admin = $this->demoUser('admin');

        $this->actingAs($admin)
            ->get(route('search.index', ['q' => "' OR 1=1 --"]))
            ->assertOk();
    }

    public function test_malicious_and_oversized_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $admin = $this->demoUser('admin');

        $this->actingAs($admin)
            ->patch(route('profile.photo.update'), [
                'photo' => UploadedFile::fake()->create('shell.php', 20, 'application/x-php'),
            ])
            ->assertSessionHasErrors('photo');

        $this->actingAs($admin)
            ->patch(route('profile.photo.update'), [
                'photo' => UploadedFile::fake()->image('../../evil.jpg'),
            ]);

        $admin->refresh();
        if (filled($admin->photo_path)) {
            $this->assertStringNotContainsString('..', $admin->photo_path);
            $this->assertStringNotContainsString('evil.jpg', $admin->photo_path);
        }

        $this->actingAs($admin)
            ->patch(route('profile.photo.update'), [
                'photo' => UploadedFile::fake()->image('big.jpg')->size(3000),
            ])
            ->assertSessionHasErrors('photo');
    }

    public function test_mass_assignment_cannot_promote_a_demo_user(): void
    {
        $sales = $this->demoUser('sales');

        $sales->fill([
            'is_super_admin' => true,
            'company_id' => null,
            'role' => 'admin',
            'status' => 'suspended',
        ])->save();

        $sales->refresh();
        $this->assertFalse($sales->is_super_admin);
        $this->assertNotNull($sales->company_id);
        $this->assertSame('user', $sales->role);
        $this->assertSame('active', $sales->status);
        $this->assertTrue($sales->hasRole('sales'));
    }

    public function test_demo_dashboard_does_not_embed_seed_password_in_javascript(): void
    {
        $admin = $this->demoUser('admin');
        $password = DemoDataSeeder::demoPassword();

        $html = $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString($password, $html);
        $this->assertStringNotContainsString('DEMO_SEED_PASSWORD', $html);
    }
}
