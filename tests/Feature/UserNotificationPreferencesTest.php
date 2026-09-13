<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadAssigned;
use App\Services\UserNotificationPreferenceService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserNotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_preferences_default_to_enabled_without_stored_records(): void
    {
        $user = User::factory()->admin()->create();
        $preferences = app(UserNotificationPreferenceService::class);

        $this->assertTrue($preferences->isEnabled($user, LeadAssigned::class, 'database'));
        $this->assertTrue($preferences->isEnabled($user, LeadAssigned::class, 'email'));
        $this->assertDatabaseCount('user_notification_preferences', 0);
    }

    public function test_profile_displays_accessible_per_channel_notification_toggles(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Notification preferences')
            ->assertSee('notification-lead_assigned-database', false)
            ->assertSee('notification-lead_assigned-email', false)
            ->assertSee('aria-describedby="notification-lead_assigned-description"', false);
    }

    public function test_user_can_update_only_their_own_notification_preferences(): void
    {
        $user = User::factory()->admin()->create();
        $otherUser = User::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->patch(route('profile.notification-preferences.update'), [
                'preferences' => $this->preferences([
                    'lead_assigned' => ['database' => '0', 'email' => '1'],
                ]),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'notification-preferences-updated');

        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $user->id,
            'notification_type' => LeadAssigned::class,
            'channel' => 'database',
            'enabled' => false,
        ]);
        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $user->id,
            'notification_type' => LeadAssigned::class,
            'channel' => 'email',
            'enabled' => true,
        ]);
        $this->assertDatabaseMissing('user_notification_preferences', ['user_id' => $otherUser->id]);
    }

    public function test_invalid_notification_preference_types_and_channels_are_rejected(): void
    {
        $user = User::factory()->admin()->create();
        $preferences = $this->preferences();
        $preferences['unexpected_type'] = ['database' => '0'];
        $preferences['lead_assigned']['unexpected_channel'] = '0';

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.notification-preferences.update'), ['preferences' => $preferences])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('preferences');

        $this->assertDatabaseCount('user_notification_preferences', 0);
    }

    public function test_disabled_database_preference_suppresses_assignment_notification_without_affecting_other_tenants(): void
    {
        $actor = User::factory()->admin()->create();
        $assignee = User::factory()->create(['company_id' => $actor->company_id]);
        $otherCompany = Company::factory()->create();
        $otherTenantUser = User::factory()->create(['company_id' => $otherCompany->id]);
        $lead = Lead::factory()->create([
            'company_id' => $actor->company_id,
            'created_by' => $actor->id,
            'assigned_to' => $actor->id,
            'status' => 'new',
        ]);

        app(UserNotificationPreferenceService::class)->update($assignee, $this->preferences([
            'lead_assigned' => ['database' => false, 'email' => true],
        ]));

        $this->actingAs($actor)->put(route('leads.update', $lead), [
            'name' => $lead->name,
            'status' => $lead->status,
            'assigned_to' => $assignee->id,
        ])->assertRedirect(route('leads.show', $lead));

        $this->assertSame(0, $assignee->notifications()->count());
        $this->assertSame(0, $otherTenantUser->notifications()->count());
        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $assignee->id,
            'notification_type' => LeadAssigned::class,
            'channel' => 'database',
            'enabled' => false,
        ]);
    }

    /**
     * @param  array<string, array<string, bool|string>>  $overrides
     * @return array<string, array<string, bool|string>>
     */
    private function preferences(array $overrides = []): array
    {
        return array_replace_recursive([
            'lead_assigned' => ['database' => '1', 'email' => '1'],
            'task_assigned' => ['database' => '1', 'email' => '1'],
            'customer_created' => ['database' => '1', 'email' => '1'],
            'website_lead_received' => ['database' => '1', 'email' => '1'],
        ], $overrides);
    }
}
