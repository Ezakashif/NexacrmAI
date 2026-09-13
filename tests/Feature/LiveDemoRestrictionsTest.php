<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Scopes\CompanyScope;
use App\Models\User;
use App\Services\RbacRoleSynchronizer;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SeedsLiveDemo;
use Tests\TestCase;

class LiveDemoRestrictionsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsLiveDemo;

    private Company $demoCompany;

    protected function setUp(): void
    {
        parent::setUp();

        $this->demoCompany = $this->seedLiveDemo();
    }

    public function test_demo_user_cannot_change_password_or_email(): void
    {
        $admin = $this->demoUser('admin');
        $originalHash = $admin->password;

        $this->actingAs($admin)
            ->put(route('password.update'), [
                'current_password' => DemoDataSeeder::demoPassword(),
                'password' => 'HackedPass1!',
                'password_confirmation' => 'HackedPass1!',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('profile.update'), [
                'name' => $admin->name,
                'email' => 'hacked-admin@example.com',
            ])
            ->assertForbidden();

        $admin->refresh();
        $this->assertSame($originalHash, $admin->password);
        $this->assertSame(DemoDataSeeder::USERS['admin']['email'], $admin->email);
    }

    public function test_seeded_demo_users_cannot_be_deleted_or_disabled(): void
    {
        $admin = $this->demoUser('admin');
        $sales = $this->demoUser('sales');

        $this->actingAs($admin)
            ->delete(route('users.destroy', $sales))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('users.status', $sales), ['status' => 'inactive'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('profile.destroy'), ['password' => DemoDataSeeder::demoPassword()])
            ->assertForbidden();

        $this->assertNotNull($sales->fresh());
        $this->assertSame('active', $sales->fresh()->status);
        $this->assertNotNull($admin->fresh());
    }

    public function test_demo_admin_cannot_change_seeded_user_roles(): void
    {
        $admin = $this->demoUser('admin');
        $sales = $this->demoUser('sales');
        $adminRoleId = Role::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->demoCompany->id)
            ->where('slug', 'admin')
            ->value('id');

        $this->actingAs($admin)
            ->put(route('users.update', $sales), [
                'name' => $sales->name,
                'email' => $sales->email,
                'roles' => [$adminRoleId],
                'status' => 'active',
            ])
            ->assertForbidden();

        $this->assertTrue($sales->fresh()->hasRole('sales'));
        $this->assertFalse($sales->fresh()->hasRole('admin'));
    }

    public function test_demo_user_cannot_change_company_slug_owner_or_plan(): void
    {
        $admin = $this->demoUser('admin');
        $originalSlug = $this->demoCompany->slug;
        $originalOwner = $this->demoCompany->owner_id;
        $originalPlan = $this->demoCompany->plan_id;

        $this->actingAs($admin)
            ->patch(route('company.settings.update'), [
                'name' => $this->demoCompany->name,
                'slug' => 'hacked-slug',
                'owner_id' => $this->demoUser('sales')->id,
                'plan_id' => 999999,
                'subscription_status' => 'expired',
            ])
            ->assertForbidden();

        $this->demoCompany->refresh();
        $this->assertSame($originalSlug, $this->demoCompany->slug);
        $this->assertSame($originalOwner, $this->demoCompany->owner_id);
        $this->assertSame($originalPlan, $this->demoCompany->plan_id);
        $this->assertSame('active', $this->demoCompany->subscription_status);
    }

    public function test_demo_user_cannot_access_super_admin_or_delete_the_demo_company(): void
    {
        $admin = $this->demoUser('admin');

        $this->actingAs($admin)
            ->get(route('superadmin.dashboard'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('superadmin.companies.destroy', $this->demoCompany))
            ->assertForbidden();

        $this->assertFalse($this->demoCompany->fresh()->trashed());
    }

    public function test_demo_user_cannot_delete_customers_inside_northstar(): void
    {
        $admin = $this->demoUser('admin');
        $customer = Customer::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->demoCompany->id)
            ->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('customers.destroy', $customer))
            ->assertForbidden();

        $this->assertNotNull($customer->fresh());
    }

    public function test_company_id_role_id_user_id_and_super_admin_manipulation_are_rejected(): void
    {
        $admin = $this->demoUser('admin');
        $other = Company::factory()->create(['slug' => 'other-tenant']);
        app(RbacRoleSynchronizer::class)->syncDefaultRolesForCompany($other);
        $foreign = User::factory()->admin()->create(['company_id' => $other->id]);

        $this->actingAs($admin)
            ->patch(route('profile.update'), [
                'name' => $admin->name,
                'email' => $admin->email,
                'company_id' => $other->id,
                'role_id' => 1,
                'user_id' => $foreign->id,
                'is_super_admin' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('users.update', $foreign), [
                'name' => 'Hacked',
                'email' => $foreign->email,
                'roles' => [1],
                'status' => 'active',
            ])
            ->assertNotFound();

        $admin->refresh();
        $this->assertSame($this->demoCompany->id, $admin->company_id);
        $this->assertFalse($admin->is_super_admin);
        $this->assertNotSame('Hacked', $foreign->fresh()->name);
    }

    public function test_forgot_password_does_not_reset_demo_accounts(): void
    {
        $admin = $this->demoUser('admin');
        $hash = $admin->password;

        $this->post(route('password.email'), [
            'email' => $admin->email,
        ])->assertRedirect();

        $this->assertSame($hash, $admin->fresh()->password);
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $admin->email,
        ]);
    }
}
