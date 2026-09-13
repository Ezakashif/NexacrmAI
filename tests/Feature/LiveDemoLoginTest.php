<?php

namespace Tests\Feature;

use App\Models\DemoVisit;
use App\Models\User;
use App\Support\DemoEnvironment;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SeedsLiveDemo;
use Tests\TestCase;

class LiveDemoLoginTest extends TestCase
{
    use RefreshDatabase;
    use SeedsLiveDemo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLiveDemo();
    }

    public function test_guest_can_view_persona_picker_without_passwords(): void
    {
        $password = DemoDataSeeder::demoPassword();

        $this->get(route('demo.select'))
            ->assertOk()
            ->assertSee('Work email', false)
            ->assertSee('Admin Demo', false)
            ->assertSee('Sales Manager Demo', false)
            ->assertSee('Sales Representative Demo', false)
            ->assertSee('Continue without email', false)
            ->assertDontSee($password, false)
            ->assertDontSee('DEMO_SEED_PASSWORD', false)
            ->assertDontSee('Demo-Northstar-2026!', false);
    }

    public function test_email_is_required_unless_continuing_anonymously(): void
    {
        $this->from(route('demo.select'))
            ->post(route('demo.start'), ['persona' => 'admin'])
            ->assertRedirect(route('demo.select'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(0, DemoVisit::query()->count());
    }

    public function test_identified_visitor_is_recorded_before_demo_login(): void
    {
        $this->get(route('demo.select'));
        $previousId = session()->getId();

        $this->post(route('demo.start'), [
            'persona' => 'admin',
            'email' => 'Alex@Company.com',
            'name' => 'Alex Morgan',
            'company' => 'Northline',
            'contact_consent' => '1',
        ], [
            'CF-IPCountry' => 'PK',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertSame(
            DemoEnvironment::emailForPersona('admin'),
            auth()->user()->email,
        );
        $this->assertNotSame($previousId, session()->getId());

        $visit = DemoVisit::query()->first();
        $this->assertNotNull($visit);
        $this->assertSame('alex@company.com', $visit->email);
        $this->assertSame('Alex Morgan', $visit->name);
        $this->assertSame('Northline', $visit->company);
        $this->assertSame('PK', $visit->country);
        $this->assertSame('admin', $visit->persona);
        $this->assertTrue($visit->contact_consent);
        $this->assertFalse($visit->is_anonymous);
        $this->assertSame(DemoVisit::STATUS_NEW, $visit->status);
    }

    public function test_unknown_cloudflare_country_codes_are_ignored(): void
    {
        $this->post(route('demo.start'), [
            'persona' => 'admin',
            'email' => 'visitor@example.com',
        ], [
            'CF-IPCountry' => 'XX',
        ])->assertRedirect(route('dashboard'));

        $this->assertNull(DemoVisit::query()->first()?->country);
    }

    public function test_anonymous_visitor_can_start_demo_without_email(): void
    {
        $this->post(route('demo.start'), [
            'persona' => 'sales',
            'anonymous' => '1',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertSame(
            DemoEnvironment::emailForPersona('sales'),
            auth()->user()->email,
        );

        $visit = DemoVisit::query()->first();
        $this->assertNotNull($visit);
        $this->assertNull($visit->email);
        $this->assertTrue($visit->is_anonymous);
        $this->assertFalse($visit->contact_consent);
        $this->assertSame('sales', $visit->persona);
    }

    public function test_each_allowlisted_persona_can_start_the_demo(): void
    {
        foreach (array_keys(DemoEnvironment::personas()) as $persona) {
            $this->post('/logout');

            $this->get(route('demo.select'));
            $previousId = session()->getId();

            $this->post(route('demo.start'), [
                'persona' => $persona,
                'email' => "visitor+{$persona}@example.com",
            ])->assertRedirect(route('dashboard'));

            $this->assertAuthenticated();
            $this->assertSame(
                DemoEnvironment::emailForPersona($persona),
                auth()->user()->email,
            );
            $this->assertNotSame($previousId, session()->getId());
            $this->assertFalse(auth()->user()->isSuperAdmin());
        }

        $this->assertSame(count(DemoEnvironment::personas()), DemoVisit::query()->count());
    }

    public function test_invalid_persona_is_rejected(): void
    {
        $this->from(route('demo.select'))
            ->post(route('demo.start'), [
                'persona' => 'superadmin',
                'email' => 'visitor@example.com',
            ])
            ->assertRedirect(route('demo.select'))
            ->assertSessionHasErrors('persona');

        $this->assertGuest();
        $this->assertSame(0, DemoVisit::query()->count());
    }

    public function test_demo_start_is_a_csrf_protected_post_route(): void
    {
        $route = app('router')->getRoutes()->getByName('demo.start');

        $this->assertNotNull($route);
        $this->assertSame(['POST'], $route->methods());
        $this->assertContains('web', $route->gatherMiddleware());

        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));
        $this->assertMatchesRegularExpression(
            '/validateCsrfTokens\\(except:\\s*\\[[^\\]]*webhooks\\/leads\\/website[^\\]]*webhooks\\/channels\\/\\*[^\\]]*\\]\\)/s',
            $bootstrap,
        );
        preg_match('/validateCsrfTokens\\(except:\\s*\\[(.*?)\\]\\)/s', $bootstrap, $match);
        $this->assertNotEmpty($match[1] ?? null);
        $this->assertStringNotContainsString('demo', $match[1]);
    }

    public function test_demo_start_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('demo.start'), ['persona' => 'not-a-persona']);
        }

        $this->postJson(route('demo.start'), [
            'persona' => 'admin',
            'email' => 'visitor@example.com',
        ])
            ->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);
    }

    public function test_login_and_register_promote_try_live_demo_without_passwords(): void
    {
        $password = DemoDataSeeder::demoPassword();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Try Live Demo', false)
            ->assertSee(route('demo.select'), false)
            ->assertDontSee($password, false);

        app(\App\Services\SuperAdmin\PlatformSettingsService::class)
            ->setMany(['registration_enabled' => true]);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Try Live Demo', false)
            ->assertDontSee($password, false);
    }
}
