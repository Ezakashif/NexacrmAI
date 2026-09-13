<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmDataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_deleting_user_nulls_created_by_instead_of_cascading_records(): void
    {
        $admin = User::factory()->admin()->create();
        $creator = User::factory()->create();

        $lead = Lead::factory()->create([
            'created_by' => $creator->id,
            'assigned_to' => $admin->id,
            'name' => 'Keep Me',
        ]);
        $customer = Customer::factory()->create([
            'created_by' => $creator->id,
            'name' => 'Keep Customer',
        ]);
        $task = Task::factory()->create([
            'created_by' => $creator->id,
            'assigned_to' => $admin->id,
            'title' => 'Keep Task',
        ]);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $creator))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $creator->id]);
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'created_by' => null]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'created_by' => null]);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'created_by' => null]);
    }

    public function test_soft_deleted_records_are_hidden_from_indexes(): void
    {
        $admin = User::factory()->admin()->create();

        $lead = Lead::factory()->create([
            'created_by' => $admin->id,
            'assigned_to' => $admin->id,
            'name' => 'Soft Lead',
            'status' => 'new',
        ]);
        $customer = Customer::factory()->create([
            'created_by' => $admin->id,
            'name' => 'Soft Customer',
        ]);
        $task = Task::factory()->create([
            'created_by' => $admin->id,
            'assigned_to' => $admin->id,
            'title' => 'Soft Task',
            'status' => 'pending',
        ]);

        $lead->delete();
        $customer->delete();
        $task->delete();

        $this->actingAs($admin)
            ->get(route('leads.index'))
            ->assertOk()
            ->assertDontSee('Soft Lead');

        $this->actingAs($admin)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertDontSee('Soft Customer');

        $this->actingAs($admin)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertDontSee('Soft Task');

        $this->assertSoftDeleted($lead);
        $this->assertSoftDeleted($customer);
        $this->assertSoftDeleted($task);
    }

    public function test_converting_restores_soft_deleted_customer_for_same_lead(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'status' => 'qualified',
            'name' => 'Restore Convert',
            'email' => 'restore@example.com',
        ]);

        $this->actingAs($user)->post(route('leads.convert', $lead))->assertRedirect();

        $customer = Customer::query()->where('source_lead_id', $lead->id)->firstOrFail();
        $customer->delete();

        $lead->refresh();
        $lead->status = 'qualified';
        $lead->save();

        $this->actingAs($user)
            ->post(route('leads.convert', $lead))
            ->assertRedirect(route('customers.show', $customer));

        $this->assertSame(1, Customer::withTrashed()->where('source_lead_id', $lead->id)->count());
        $this->assertFalse($customer->fresh()->trashed());
        $this->assertSame('won', $lead->fresh()->status);
    }
}
