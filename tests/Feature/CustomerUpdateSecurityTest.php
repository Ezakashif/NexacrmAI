<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerUpdateSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_customer_update_ignores_mass_assignment_of_protected_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create();
        $lead = Lead::factory()->create(['created_by' => $admin->id]);

        $customer = Customer::factory()->create([
            'created_by' => $admin->id,
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'status' => 'active',
            'source_lead_id' => null,
        ]);

        $this->actingAs($admin)
            ->put(route('customers.update', $customer), [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'phone' => '555-0100',
                'company_name' => 'Updated Co',
                'address' => '123 Main',
                'notes' => 'Safe notes',
                'created_by' => $other->id,
                'source_lead_id' => $lead->id,
                'status' => 'inactive',
            ])
            ->assertRedirect(route('customers.index'));

        $customer->refresh();

        $this->assertSame('Updated Name', $customer->name);
        $this->assertSame('updated@example.com', $customer->email);
        $this->assertSame('555-0100', $customer->phone);
        $this->assertSame('Updated Co', $customer->company_name);
        $this->assertSame('123 Main', $customer->address);
        $this->assertSame('Safe notes', $customer->notes);
        $this->assertSame($admin->id, $customer->created_by);
        $this->assertNull($customer->source_lead_id);
        $this->assertSame('active', $customer->status);
    }

    public function test_lead_conversion_is_idempotent(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'status' => 'qualified',
            'name' => 'Convert Once',
            'email' => 'once@example.com',
        ]);

        $this->actingAs($user)
            ->post(route('leads.convert', $lead))
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('leads.convert', $lead))
            ->assertRedirect();

        $this->assertSame(1, Customer::query()->where('source_lead_id', $lead->id)->count());
    }
}
