<?php

namespace Tests\Feature;

use App\Models\Plan;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_seeder_creates_three_public_marketing_plans(): void
    {
        $this->seed(PlanSeeder::class);

        $this->assertDatabaseHas('plans', [
            'slug' => 'starter',
            'is_default' => 1,
            'is_public' => 1,
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('plans', [
            'slug' => 'professional',
            'is_featured' => 1,
        ]);
        $this->assertDatabaseHas('plans', [
            'slug' => 'enterprise',
            'trial_days' => 0,
        ]);

        $starter = Plan::query()->where('slug', 'starter')->with(['features', 'limits'])->firstOrFail();
        $this->assertNotEmpty($starter->features);
        $this->assertNotEmpty($starter->limits);
        $this->assertSame(3, Plan::query()->where('is_public', true)->where('is_active', true)->count());
    }

    public function test_plan_seeder_is_idempotent(): void
    {
        $this->seed(PlanSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->assertSame(1, Plan::withTrashed()->where('slug', 'starter')->count());
        $this->assertSame(3, Plan::query()->whereIn('slug', ['starter', 'professional', 'enterprise'])->count());
    }
}
