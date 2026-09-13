<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Services\RbacRoleSynchronizer;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossTenantCustomerDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_user_in_company_a_cannot_access_or_delete_customer_in_company_b(): void
    {
        $this->seed(RbacSeeder::class);

        $companyA = Company::factory()->create(['slug' => 'company-a']);
        $companyB = Company::factory()->create(['slug' => 'company-b']);
        app(RbacRoleSynchronizer::class)->syncDefaultRolesForCompany($companyA);
        app(RbacRoleSynchronizer::class)->syncDefaultRolesForCompany($companyB);

        $salesA = User::factory()->create(['company_id' => $companyA->id]);
        $adminB = User::factory()->admin()->create(['company_id' => $companyB->id]);

        $this->assertTrue($salesA->hasPermission('delete.customers'));

        $customerB = Customer::factory()->create([
            'company_id' => $companyB->id,
            'created_by' => $adminB->id,
            'name' => 'Customer B',
        ]);

        $this->assertFalse($salesA->can('view', $customerB));
        $this->assertFalse($salesA->can('delete', $customerB));

        $this->actingAs($salesA)
            ->get(route('customers.show', $customerB))
            ->assertNotFound();

        $this->actingAs($salesA)
            ->delete(route('customers.destroy', $customerB))
            ->assertNotFound();

        $this->assertDatabaseHas('customers', [
            'id' => $customerB->id,
            'company_id' => $companyB->id,
        ]);
    }
}
