<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiPhase2CCustomersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_customers_index_uses_shared_header_and_empty_state(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('crm-page-header', false)
            ->assertSee('No customers yet')
            ->assertSee('Add customer')
            ->assertSee('data-crm-confirm', false);
    }

    public function test_customers_create_edit_show_use_shared_patterns(): void
    {
        $user = User::factory()->admin()->create();
        $customer = Customer::factory()->create([
            'created_by' => $user->id,
            'name' => 'Acme Profile',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('customers.create'))
            ->assertOk()
            ->assertSee('crm-form-section', false)
            ->assertSee('Create customer');

        $this->actingAs($user)
            ->get(route('customers.edit', $customer))
            ->assertOk()
            ->assertSee('Edit customer')
            ->assertSee('Acme Profile');

        $this->actingAs($user)
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Customer details')
            ->assertSee('Customer timeline')
            ->assertSee('Delete customer')
            ->assertSee('data-crm-confirm', false);
    }
}
