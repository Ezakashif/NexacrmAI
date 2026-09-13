<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Scopes\CompanyScope;
use App\Models\User;
use App\Services\RbacRoleSynchronizer;
use App\Support\DemoEnvironment;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Feature\Concerns\SeedsLiveDemo;
use Tests\TestCase;

class LiveDemoResetTest extends TestCase
{
    use RefreshDatabase;
    use SeedsLiveDemo;

    public function test_reset_is_isolated_to_northstar_and_idempotent(): void
    {
        $demo = $this->seedLiveDemo();

        $other = Company::factory()->create(['slug' => 'production-tenant']);
        app(RbacRoleSynchronizer::class)->syncDefaultRolesForCompany($other);
        $otherAdmin = User::factory()->admin()->create([
            'company_id' => $other->id,
            'email' => 'ops@production-tenant.test',
        ]);
        $otherLead = Lead::factory()->create([
            'company_id' => $other->id,
            'created_by' => $otherAdmin->id,
            'assigned_to' => $otherAdmin->id,
            'name' => 'Production Lead Must Survive',
        ]);
        $otherCustomer = Customer::factory()->create([
            'company_id' => $other->id,
            'created_by' => $otherAdmin->id,
            'name' => 'Production Customer Must Survive',
        ]);

        $visitorLead = Lead::factory()->create([
            'company_id' => $demo->id,
            'created_by' => $this->demoUser('sales')->id,
            'assigned_to' => $this->demoUser('sales')->id,
            'email' => 'visitor@example.com',
            'name' => 'Visitor Created Lead',
        ]);

        $this->artisan('demo:reset')->assertSuccessful();
        $this->artisan('demo:reset')->assertSuccessful();

        $this->assertSame(1, Company::query()->where('slug', DemoDataSeeder::COMPANY_SLUG)->count());
        $this->assertSame(
            18,
            Lead::withoutGlobalScope(CompanyScope::class)->where('company_id', $demo->id)->count(),
        );
        $this->assertSame(
            8,
            Customer::withoutGlobalScope(CompanyScope::class)->where('company_id', $demo->id)->count(),
        );
        $this->assertSame(
            3,
            User::withoutGlobalScope(CompanyScope::class)->where('company_id', $demo->id)->count(),
        );

        $this->assertDatabaseMissing('leads', ['id' => $visitorLead->id]);
        $this->assertDatabaseHas('leads', [
            'id' => $otherLead->id,
            'company_id' => $other->id,
            'name' => 'Production Lead Must Survive',
        ]);
        $this->assertDatabaseHas('customers', [
            'id' => $otherCustomer->id,
            'company_id' => $other->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $otherAdmin->id,
            'email' => 'ops@production-tenant.test',
        ]);
    }

    public function test_reset_aborts_when_demo_company_is_missing(): void
    {
        $this->seed(RbacSeeder::class);

        $this->artisan('demo:reset')->assertFailed();

        $this->assertSame(0, Company::query()->where('slug', DemoDataSeeder::COMPANY_SLUG)->count());
        $this->assertNotNull(Company::query()->where('slug', Company::DEFAULT_SLUG)->first());
    }

    public function test_assert_resettable_company_refuses_the_default_company(): void
    {
        $this->seed(RbacSeeder::class);
        $default = Company::query()->where('slug', Company::DEFAULT_SLUG)->firstOrFail();

        $this->expectException(RuntimeException::class);
        DemoEnvironment::assertResettableCompany($default);
    }

    public function test_reset_only_deletes_files_belonging_to_northstar_records(): void
    {
        Storage::fake('public');
        $demo = $this->seedLiveDemo();

        $demoPath = 'avatars/northstar-user.jpg';
        $otherPath = 'avatars/other-tenant.jpg';
        Storage::disk('public')->put($demoPath, 'demo');
        Storage::disk('public')->put($otherPath, 'other');

        $this->demoUser('sales')->forceFill(['photo_path' => $demoPath])->save();

        $other = Company::factory()->create(['slug' => 'other-files']);
        app(RbacRoleSynchronizer::class)->syncDefaultRolesForCompany($other);
        User::factory()->create([
            'company_id' => $other->id,
            'photo_path' => $otherPath,
        ]);

        $this->artisan('demo:reset')->assertSuccessful();

        Storage::disk('public')->assertMissing($demoPath);
        Storage::disk('public')->assertExists($otherPath);
        $this->assertSame($demo->id, Company::query()->where('slug', DemoDataSeeder::COMPANY_SLUG)->value('id'));
    }

    public function test_daily_demo_reset_is_scheduled_with_overlap_protection(): void
    {
        $events = collect(app(Schedule::class)->events());

        $match = $events->first(function ($event) {
            $command = (string) ($event->command ?? '');

            return str_contains($command, 'demo:reset');
        });

        $this->assertNotNull($match);
        $this->assertTrue($match->withoutOverlapping);
        $this->assertFalse((bool) config('demo.reset_enabled'));
    }
}
