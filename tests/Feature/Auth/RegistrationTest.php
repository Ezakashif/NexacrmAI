<?php

namespace Tests\Feature\Auth;

use App\Services\SuperAdmin\PlatformSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_routes_are_disabled(): void
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'company_name' => 'Test Co',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'SecurePass1!',
            'password_confirmation' => 'SecurePass1!',
        ])->assertNotFound();

        $this->assertGuest();
    }

    public function test_registration_routes_work_when_enabled(): void
    {
        app(PlatformSettingsService::class)->setMany(['registration_enabled' => true]);

        $this->get('/register')->assertOk();
    }
}
