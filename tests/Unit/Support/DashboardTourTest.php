<?php

namespace Tests\Unit\Support;

use App\Models\User;
use App\Support\DashboardTour;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTourTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_steps_for_admin_include_core_lifecycle_entries(): void
    {
        $admin = User::factory()->admin()->create();

        $ids = collect(DashboardTour::stepsFor($admin))->pluck('id')->all();

        $this->assertContains('welcome', $ids);
        $this->assertContains('leads', $ids);
        $this->assertContains('customers', $ids);
        $this->assertContains('tasks', $ids);
        $this->assertContains('replay', $ids);
    }

    public function test_steps_omit_modules_without_permission(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);
        $user->roles()->detach();
        $user->unsetRelation('roles');

        // Clear cached permission slugs if the model instance cached them.
        $ref = new \ReflectionClass($user);
        if ($ref->hasProperty('cachedPermissionSlugs')) {
            $prop = $ref->getProperty('cachedPermissionSlugs');
            $prop->setAccessible(true);
            $prop->setValue($user, null);
        }

        $ids = collect(DashboardTour::stepsFor($user->fresh()))->pluck('id')->all();

        $this->assertContains('welcome', $ids);
        $this->assertContains('replay', $ids);
        $this->assertNotContains('leads', $ids);
        $this->assertNotContains('reports', $ids);
    }
}
