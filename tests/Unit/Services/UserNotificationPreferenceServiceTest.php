<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Notifications\LeadAssigned;
use App\Notifications\TaskAssigned;
use App\Services\UserNotificationPreferenceService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class UserNotificationPreferenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserNotificationPreferenceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->service = app(UserNotificationPreferenceService::class);
    }

    public function test_is_enabled_defaults_to_true_without_stored_records(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($this->service->isEnabled($user, LeadAssigned::class, 'database'));
        $this->assertTrue($this->service->isEnabled($user, LeadAssigned::class, 'email'));
        $this->assertDatabaseCount('user_notification_preferences', 0);
    }

    public function test_for_user_returns_defaults_for_all_configured_types_and_channels(): void
    {
        $user = User::factory()->admin()->create();

        $preferences = $this->service->forUser($user);

        $this->assertArrayHasKey('lead_assigned', $preferences);
        $this->assertArrayHasKey('task_assigned', $preferences);
        $this->assertArrayHasKey('database', $preferences['lead_assigned']);
        $this->assertArrayHasKey('email', $preferences['lead_assigned']);
        $this->assertTrue($preferences['lead_assigned']['database']);
        $this->assertTrue($preferences['website_lead_received']['email']);
    }

    public function test_update_upserts_preferences_by_notification_class(): void
    {
        $user = User::factory()->admin()->create();

        $this->service->update($user, [
            'lead_assigned' => ['database' => false, 'email' => true],
            'task_assigned' => ['database' => '0', 'email' => '1'],
        ]);

        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $user->id,
            'notification_type' => LeadAssigned::class,
            'channel' => 'database',
            'enabled' => false,
        ]);
        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $user->id,
            'notification_type' => TaskAssigned::class,
            'channel' => 'database',
            'enabled' => false,
        ]);
        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $user->id,
            'notification_type' => TaskAssigned::class,
            'channel' => 'email',
            'enabled' => true,
        ]);

        $this->assertFalse($this->service->isEnabled($user->fresh(), LeadAssigned::class, 'database'));
        $this->assertTrue($this->service->isEnabled($user->fresh(), LeadAssigned::class, 'email'));
    }

    public function test_update_overwrites_existing_preference_records(): void
    {
        $user = User::factory()->admin()->create();

        $user->notificationPreferences()->create([
            'notification_type' => LeadAssigned::class,
            'channel' => 'database',
            'enabled' => true,
        ]);

        $this->service->update($user, [
            'lead_assigned' => ['database' => false],
        ]);

        $this->assertDatabaseCount('user_notification_preferences', 1);
        $this->assertFalse($this->service->isEnabled($user->fresh(), LeadAssigned::class, 'database'));
    }

    public function test_is_enabled_rejects_unknown_notification_type(): void
    {
        $user = User::factory()->admin()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown notification preference type.');

        $this->service->isEnabled($user, 'App\\Notifications\\UnknownNotification', 'database');
    }

    public function test_update_rejects_unknown_notification_type(): void
    {
        $user = User::factory()->admin()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown notification preference type.');

        $this->service->update($user, [
            'unexpected_type' => ['database' => false],
        ]);
    }

    public function test_update_rejects_unknown_channel(): void
    {
        $user = User::factory()->admin()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown notification preference channel.');

        $this->service->update($user, [
            'lead_assigned' => ['sms' => false],
        ]);
    }
}
