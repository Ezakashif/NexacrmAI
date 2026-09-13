<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\RbacRoleSynchronizer;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncPermissionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_permissions_sync_removes_manager_and_clears_sales_system_flag(): void
    {
        $this->seed(RbacSeeder::class);

        Role::query()->where('slug', 'sales')->update(['is_system' => true]);

        $manager = Role::query()->create([
            'name' => 'Manager',
            'slug' => 'manager',
            'description' => 'Legacy system role',
            'is_system' => true,
        ]);

        $legacyUser = User::factory()->create(['role' => 'user']);
        $legacyUser->roles()->sync([$manager->id]);

        $this->artisan('permissions:sync')->assertSuccessful();

        $this->assertDatabaseMissing('roles', ['slug' => 'manager']);
        $this->assertDatabaseHas('roles', ['slug' => 'admin', 'is_system' => true]);
        $this->assertDatabaseHas('roles', ['slug' => 'sales', 'is_system' => false]);
        $this->assertSame(1, Role::query()->where('is_system', true)->count());
        $this->assertTrue($legacyUser->fresh()->hasRole('sales'));
        $this->assertFalse($legacyUser->fresh()->hasRole('manager'));
    }

    public function test_role_synchronizer_force_clears_non_admin_system_flags(): void
    {
        $this->seed(RbacSeeder::class);

        Role::query()->create([
            'name' => 'Manager',
            'slug' => 'manager',
            'description' => 'Legacy',
            'is_system' => true,
        ]);

        Role::query()->where('slug', 'sales')->update(['is_system' => true]);

        app(RbacRoleSynchronizer::class)->syncDefaultRoles();

        $this->assertDatabaseMissing('roles', ['slug' => 'manager']);
        $this->assertFalse((bool) Role::query()->where('slug', 'sales')->value('is_system'));
        $this->assertTrue((bool) Role::query()->where('slug', 'admin')->value('is_system'));
    }
}
