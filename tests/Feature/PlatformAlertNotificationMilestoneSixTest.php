<?php

namespace Tests\Feature;

use App\Jobs\SendPlatformAlertJob;
use App\Models\Company;
use App\Models\User;
use App\Notifications\PlatformAlertDetected;
use App\Services\SuperAdmin\PlatformAlertNotificationService;
use App\Services\SuperAdmin\PlatformAlertService;
use App\Services\UserNotificationPreferenceService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class PlatformAlertNotificationMilestoneSixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_delivery_targets_only_active_platform_super_admins_and_uses_a_safe_url(): void
    {
        $activeSuperAdmin = User::factory()->superAdmin()->create();
        $inactiveSuperAdmin = User::factory()->superAdmin()->inactive()->create();
        $tenantCompany = Company::factory()->create();
        $tenantAdmin = User::factory()->admin()->create(['company_id' => $tenantCompany->id]);
        $tenantScopedSuperAdmin = User::factory()->create([
            'company_id' => $tenantCompany->id,
            'is_super_admin' => true,
        ]);

        app(PlatformAlertNotificationService::class)->deliver($this->dangerAlert());

        $notification = $activeSuperAdmin->notifications()->firstOrFail();
        $this->assertSame(PlatformAlertDetected::class, $notification->type);
        $this->assertSame('Companies exceeding plan limits', $notification->data['subject']);
        $this->assertSame(route('superadmin.dashboard', [], false), $notification->data['url']);
        $this->assertStringStartsWith('/', $notification->data['url']);
        $this->assertArrayNotHasKey('meta', $notification->data);
        $this->assertArrayNotHasKey('company_id', $notification->data);
        $this->assertSame(0, $inactiveSuperAdmin->notifications()->count());
        $this->assertSame(0, $tenantAdmin->notifications()->count());
        $this->assertSame(0, $tenantScopedSuperAdmin->notifications()->count());
    }

    public function test_repeated_delivery_of_the_same_alert_is_deduplicated_but_changed_state_is_not(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $notifications = app(PlatformAlertNotificationService::class);

        $this->assertSame(1, $notifications->deliver($this->dangerAlert()));
        $this->assertSame(0, $notifications->deliver($this->dangerAlert()));
        $this->assertSame(1, $notifications->deliver($this->dangerAlert(['count' => 3])));

        $this->assertSame(2, $superAdmin->notifications()->count());
        $this->assertDatabaseCount('platform_alert_deliveries', 1);
    }

    public function test_future_database_preferences_are_respected_when_the_type_is_configured(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        config()->set('user_notification_preferences.types.platform_alert', [
            'class' => PlatformAlertDetected::class,
            'label' => 'Platform alerts',
            'description' => 'Danger-severity platform health alerts.',
        ]);

        app(UserNotificationPreferenceService::class)->update($superAdmin, [
            'platform_alert' => ['database' => false],
        ]);

        app(PlatformAlertNotificationService::class)->deliver($this->dangerAlert());

        $this->assertSame(0, $superAdmin->notifications()->count());
    }

    public function test_command_queues_only_danger_platform_alerts(): void
    {
        Queue::fake();

        $alerts = Mockery::mock(PlatformAlertService::class);
        $alerts->shouldReceive('alerts')->once()->andReturn([
            $this->dangerAlert(),
            [
                'type' => 'companies_inactive',
                'severity' => 'warning',
                'title' => 'Inactive companies',
                'message' => 'One active company has no activity.',
                'meta' => ['count' => 1],
            ],
        ]);
        $this->app->instance(PlatformAlertService::class, $alerts);

        $this->artisan('platform:send-alert-notifications')
            ->expectsOutput('Dispatched 1 platform alert notification job(s).')
            ->assertSuccessful();

        Queue::assertPushed(SendPlatformAlertJob::class, function (SendPlatformAlertJob $job): bool {
            return $job->alert['type'] === 'companies_over_limit';
        });
        Queue::assertPushed(SendPlatformAlertJob::class, 1);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array{type: string, severity: string, title: string, message: string, meta: array<string, mixed>}
     */
    private function dangerAlert(array $meta = ['count' => 2]): array
    {
        return [
            'type' => 'companies_over_limit',
            'severity' => 'danger',
            'title' => 'Companies exceeding plan limits',
            'message' => '2 companies exceed plan user, lead, or customer limits.',
            'meta' => $meta,
        ];
    }
}
