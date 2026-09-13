<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use App\Notifications\PasswordResetNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class MediumPriorityAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_password_reset_works_by_email_without_workspace_slug(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset-me@example.com',
            'password' => Hash::make('password-a'),
        ]);

        $this->post(route('password.email'), [
            'email' => 'reset-me@example.com',
        ])->assertSessionHas('status');

        Notification::assertSentTo($user, PasswordResetNotification::class);

        $token = Password::broker()->createToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => 'reset-me@example.com',
            'password' => 'SecurePass1!',
            'password_confirmation' => 'SecurePass1!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('SecurePass1!', $user->fresh()->password));
    }

    public function test_default_company_cannot_be_suspended(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $default = Company::default();

        $this->actingAs($superAdmin)
            ->patch(route('superadmin.companies.status', $default), [
                'status' => 'suspended',
            ])
            ->assertSessionHasErrors('company');

        $this->assertSame(Company::STATUS_ACTIVE, $default->fresh()->status);
    }

    public function test_soft_deleted_company_can_be_restored(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create(['slug' => 'restore-me']);

        $company->delete();

        $this->actingAs($superAdmin)
            ->post(route('superadmin.companies.restore', $company->id))
            ->assertRedirect(route('superadmin.companies.show', $company->id));

        $this->assertFalse($company->fresh()->trashed());
    }

    public function test_report_rejects_date_range_over_one_year(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('reports.index', [
                'date_from' => now()->subDays(400)->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertSessionHasErrors('date_to');
    }

    public function test_search_escapes_like_wildcards(): void
    {
        $admin = User::factory()->admin()->create();

        \App\Models\Lead::factory()->create([
            'company_id' => $admin->company_id,
            'created_by' => $admin->id,
            'assigned_to' => $admin->id,
            'name' => '100% Growth',
        ]);
        \App\Models\Lead::factory()->create([
            'company_id' => $admin->company_id,
            'created_by' => $admin->id,
            'assigned_to' => $admin->id,
            'name' => '100X Growth',
        ]);

        $this->actingAs($admin)
            ->get(route('search.index', ['q' => '100%']))
            ->assertOk()
            ->assertSee('100% Growth')
            ->assertDontSee('100X Growth');
    }

    public function test_activity_logs_prune_command_deletes_old_rows(): void
    {
        $user = User::factory()->admin()->create();

        $old = \App\Models\ActivityLog::query()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => 'lead.created',
            'subject_type' => \App\Models\Lead::class,
            'subject_id' => 1,
            'properties' => ['name' => 'Old'],
        ]);
        $old->forceFill([
            'created_at' => now()->subDays(120),
            'updated_at' => now()->subDays(120),
        ])->saveQuietly();

        $recent = \App\Models\ActivityLog::query()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => 'lead.created',
            'subject_type' => \App\Models\Lead::class,
            'subject_id' => 2,
            'properties' => ['name' => 'Recent'],
        ]);

        $this->artisan('activity-logs:prune', ['--days' => 90])
            ->assertSuccessful();

        $this->assertDatabaseMissing('activity_logs', ['id' => $old->id]);
        $this->assertDatabaseHas('activity_logs', ['id' => $recent->id]);
    }
}
