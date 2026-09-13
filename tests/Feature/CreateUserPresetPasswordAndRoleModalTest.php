<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\UserCredentialsNotification;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CreateUserPresetPasswordAndRoleModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_create_user_page_shows_credentials_option_and_role_modal(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('users.create'));

        $response->assertOk()
            ->assertSee('Email this password to the user after creation', false)
            ->assertSee('Generate password', false)
            ->assertSee('Create role &amp; assign permissions', false)
            ->assertSee('id="createRoleModal"', false)
            ->assertSee('modal-dialog-scrollable', false)
            ->assertSee('id="create-role-modal-form"', false)
            ->assertSee(route('roles.store'), false);
    }

    public function test_creating_user_with_email_credentials_sends_password_email(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $salesRoleId = Role::query()
            ->where('company_id', $admin->company_id)
            ->where('slug', 'sales')
            ->value('id');

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Preset User',
            'email' => 'preset@example.com',
            'password' => 'SecurePass1!',
            'password_confirmation' => 'SecurePass1!',
            'email_credentials' => '1',
            'roles' => [$salesRoleId],
            'status' => 'active',
        ]);

        $response->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $user = User::query()->where('email', 'preset@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(password_verify('SecurePass1!', $user->password));

        Notification::assertSentTo(
            $user,
            UserCredentialsNotification::class,
            function (UserCredentialsNotification $notification) use ($admin) {
                return $notification->temporaryPassword === 'SecurePass1!'
                    && $notification->companyName === $admin->company->name;
            }
        );
    }

    public function test_creating_user_without_email_credentials_does_not_send_password_email(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $salesRoleId = Role::query()
            ->where('company_id', $admin->company_id)
            ->where('slug', 'sales')
            ->value('id');

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Quiet User',
            'email' => 'quiet@example.com',
            'password' => 'SecurePass1!',
            'password_confirmation' => 'SecurePass1!',
            'email_credentials' => '0',
            'roles' => [$salesRoleId],
            'status' => 'active',
        ])->assertRedirect(route('users.index'));

        $user = User::query()->where('email', 'quiet@example.com')->firstOrFail();

        Notification::assertNotSentTo($user, UserCredentialsNotification::class);
    }

    public function test_creating_user_succeeds_when_credentials_email_fails(): void
    {
        $admin = User::factory()->admin()->create();
        $salesRoleId = Role::query()
            ->where('company_id', $admin->company_id)
            ->where('slug', 'sales')
            ->value('id');

        Notification::swap(new class
        {
            public function send($notifiables, $notification): void
            {
                throw new \RuntimeException('SMTP unavailable');
            }

            public function sendNow($notifiables, $notification, ?array $channels = null): void
            {
                throw new \RuntimeException('SMTP unavailable');
            }

            public function locale($locale): self
            {
                return $this;
            }
        });

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Mail Fail User',
            'email' => 'mailfail@example.com',
            'password' => 'SecurePass1!',
            'password_confirmation' => 'SecurePass1!',
            'email_credentials' => '1',
            'roles' => [$salesRoleId],
            'status' => 'active',
        ]);

        $response->assertRedirect(route('users.index'))
            ->assertSessionHas('success')
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('users', [
            'email' => 'mailfail@example.com',
            'company_id' => $admin->company_id,
        ]);
    }

    public function test_admin_can_create_role_via_json_from_create_user_modal(): void
    {
        $admin = User::factory()->admin()->create();
        $permissionIds = Permission::query()
            ->whereIn('slug', ['view.leads', 'view.customers'])
            ->pluck('id')
            ->all();

        $response = $this->actingAs($admin)
            ->postJson(route('roles.store'), [
                'name' => 'Support Desk',
                'slug' => 'support_desk',
                'description' => 'Created from user form',
                'permissions' => $permissionIds,
            ]);

        $response->assertCreated()
            ->assertJsonPath('role.slug', 'support_desk')
            ->assertJsonPath('role.name', 'Support Desk');

        $role = Role::query()->where('slug', 'support_desk')->first();
        $this->assertNotNull($role);
        $this->assertSame($admin->company_id, $role->company_id);
        $this->assertCount(2, $role->permissions);
    }
}
