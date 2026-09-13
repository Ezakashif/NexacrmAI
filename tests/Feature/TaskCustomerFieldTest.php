<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCustomerFieldTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_create_form_shows_related_customer_dropdown(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = Customer::factory()->create([
            'created_by' => $admin->id,
            'name' => 'Acme Customer',
            'company_name' => 'Acme Inc',
        ]);

        $this->actingAs($admin)
            ->get(route('tasks.create'))
            ->assertOk()
            ->assertSee('Related customer')
            ->assertSee('Acme Customer')
            ->assertSee('Acme Inc');
    }

    public function test_admin_can_create_task_with_related_customer(): void
    {
        $admin = User::factory()->admin()->create();
        $assignee = User::factory()->create();
        $customer = Customer::factory()->create([
            'created_by' => $admin->id,
            'name' => 'Linked Customer',
        ]);

        $this->actingAs($admin)
            ->post(route('tasks.store'), [
                'title' => 'Follow up with customer',
                'description' => 'Call next week',
                'priority' => 'high',
                'due_date' => now()->addDays(3)->toDateString(),
                'assigned_to' => $assignee->id,
                'customer_id' => $customer->id,
            ])
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'title' => 'Follow up with customer',
            'customer_id' => $customer->id,
            'assigned_to' => $assignee->id,
            'created_by' => $admin->id,
        ]);
    }

    public function test_admin_can_update_task_related_customer(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = Customer::factory()->create([
            'created_by' => $admin->id,
            'name' => 'Updated Customer',
        ]);

        $task = Task::factory()->assignedTo($admin)->create([
            'created_by' => $admin->id,
            'customer_id' => null,
            'title' => 'Editable Task',
        ]);

        $this->actingAs($admin)
            ->put(route('tasks.update', $task), [
                'title' => 'Editable Task',
                'description' => $task->description,
                'priority' => 'medium',
                'status' => 'pending',
                'due_date' => null,
                'assigned_to' => $admin->id,
                'customer_id' => $customer->id,
            ])
            ->assertRedirect(route('tasks.index'));

        $this->assertSame($customer->id, $task->fresh()->customer_id);
    }

    public function test_task_can_be_saved_without_related_customer(): void
    {
        $admin = User::factory()->admin()->create();
        $assignee = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('tasks.store'), [
                'title' => 'Internal task',
                'priority' => 'low',
                'assigned_to' => $assignee->id,
                'customer_id' => '',
            ])
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'title' => 'Internal task',
            'customer_id' => null,
        ]);
    }
}
