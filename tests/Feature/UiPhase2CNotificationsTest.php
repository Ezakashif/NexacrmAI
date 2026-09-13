<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadFollowUpDue;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UiPhase2CNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_notifications_index_uses_shared_header_and_empty_state(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('crm-page-header', false)
            ->assertSee('No notifications yet')
            ->assertSee('crm-empty', false);
    }

    public function test_notifications_list_shows_unread_item_and_mark_all(): void
    {
        $user = User::factory()->admin()->create();
        $lead = Lead::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'name' => 'Follow Up Lead',
            'follow_up_date' => now()->toDateString(),
        ]);

        $user->notifyNow(new LeadFollowUpDue($lead, 'due'));

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('crm-notification-list', false)
            ->assertSee('is-unread', false)
            ->assertSee('Mark all as read')
            ->assertSee('View lead')
            ->assertSee('Follow-up due today');
    }

    public function test_mark_all_as_read_still_works(): void
    {
        $user = User::factory()->admin()->create();
        $lead = Lead::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'follow_up_date' => now()->toDateString(),
        ]);

        $user->notifyNow(new LeadFollowUpDue($lead, 'due'));

        $this->assertSame(1, $user->unreadNotifications()->count());

        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }
}
