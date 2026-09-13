<?php

namespace Tests\Unit\Services\SuperAdmin;

use App\Models\Company;
use App\Models\ImpersonationLog;
use App\Models\User;
use App\Services\SuperAdmin\ImpersonationService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ImpersonationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ImpersonationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->service = app(ImpersonationService::class);
    }

    public function test_start_impersonation_logs_in_company_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->create([
            'company_id' => $company->id,
            'email' => 'tenant-admin@example.com',
        ]);

        $request = $this->sessionRequest($superAdmin);

        $target = $this->service->start($superAdmin, $company, $request);

        $this->assertSame($admin->id, $target->id);
        $this->assertAuthenticatedAs($admin);
        $this->assertSame($superAdmin->id, $request->session()->get(ImpersonationService::SESSION_IMPERSONATOR_ID));

        $this->assertDatabaseHas('impersonation_logs', [
            'super_admin_id' => $superAdmin->id,
            'target_user_id' => $admin->id,
            'company_id' => $company->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'impersonation.started',
            'user_id' => $superAdmin->id,
        ]);
    }

    public function test_start_fails_for_non_super_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        User::factory()->admin()->create(['company_id' => $company->id]);

        $request = $this->sessionRequest($admin);

        try {
            $this->service->start($admin, $company, $request);
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_start_fails_when_already_impersonating(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create();
        User::factory()->admin()->create(['company_id' => $company->id]);

        $request = $this->sessionRequest($superAdmin);
        $request->session()->put(ImpersonationService::SESSION_IMPERSONATOR_ID, $superAdmin->id);

        try {
            $this->service->start($superAdmin, $company, $request);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'You are already impersonating a user. Return to Super Admin first.',
                $exception->errors()['impersonation'][0],
            );
        }
    }

    public function test_start_fails_without_active_company_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create();

        $request = $this->sessionRequest($superAdmin);

        try {
            $this->service->start($superAdmin, $company, $request);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'This company has no active admin user to login as.',
                $exception->errors()['impersonation'][0],
            );
        }
    }

    public function test_start_fails_for_suspended_company(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->suspended()->create();
        User::factory()->admin()->create(['company_id' => $company->id]);

        $request = $this->sessionRequest($superAdmin);

        try {
            $this->service->start($superAdmin, $company, $request);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Cannot login to a suspended company.',
                $exception->errors()['impersonation'][0],
            );
        }
    }

    public function test_stop_impersonation_restores_super_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);

        $log = ImpersonationLog::query()->create([
            'super_admin_id' => $superAdmin->id,
            'target_user_id' => $admin->id,
            'company_id' => $company->id,
            'ip_address' => '127.0.0.1',
            'started_at' => now(),
        ]);

        $request = $this->sessionRequest($admin);
        $request->session()->put(ImpersonationService::SESSION_IMPERSONATOR_ID, $superAdmin->id);
        $request->session()->put(ImpersonationService::SESSION_IMPERSONATION_LOG_ID, $log->id);

        $restored = $this->service->stop($request);

        $this->assertSame($superAdmin->id, $restored->id);
        $this->assertAuthenticatedAs($superAdmin);
        $this->assertFalse($request->session()->has(ImpersonationService::SESSION_IMPERSONATOR_ID));
        $this->assertNotNull($log->fresh()->ended_at);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'impersonation.ended',
            'user_id' => $superAdmin->id,
        ]);
    }

    public function test_stop_fails_when_not_impersonating(): void
    {
        $user = User::factory()->create();
        $request = $this->sessionRequest($user);

        try {
            $this->service->stop($request);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'You are not currently impersonating a user.',
                $exception->errors()['impersonation'][0],
            );
        }
    }

    public function test_stop_clears_session_when_super_admin_missing(): void
    {
        $admin = User::factory()->admin()->create();
        $request = $this->sessionRequest($admin);
        $request->session()->put(ImpersonationService::SESSION_IMPERSONATOR_ID, 999999);
        $request->session()->put(ImpersonationService::SESSION_IMPERSONATION_LOG_ID, 123);

        try {
            $this->service->stop($request);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The original Super Admin account could not be restored.',
                $exception->errors()['impersonation'][0],
            );
        }

        $this->assertFalse($request->session()->has(ImpersonationService::SESSION_IMPERSONATOR_ID));
        $this->assertGuest();
    }

    public function test_is_impersonating_reflects_session_state(): void
    {
        $user = User::factory()->create();
        $request = $this->sessionRequest($user);

        $this->assertFalse($this->service->isImpersonating($request));

        $request->session()->put(ImpersonationService::SESSION_IMPERSONATOR_ID, 1);

        $this->assertTrue($this->service->isImpersonating($request));
    }

    private function sessionRequest(User $user): Request
    {
        $session = new Store('test', new ArraySessionHandler(120));
        $session->start();

        $request = Request::create('/', 'GET');
        $request->setLaravelSession($session);
        $request->setUserResolver(fn () => $user);

        Auth::setUser($user);

        return $request;
    }
}
