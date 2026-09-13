<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskBoardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_sales_user_can_drag_own_task_to_any_status(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'status' => 'pending',
            'title' => 'Drag me',
        ]);

        foreach (['in_progress', 'completed', 'cancelled', 'pending'] as $status) {
            $this->actingAs($user)
                ->postJson(route('tasks.board.update'), [
                    'task_id' => $task->id,
                    'status' => $status,
                    'sort_order' => 1,
                ])
                ->assertOk()
                ->assertJson(['success' => true]);

            $this->assertSame($status, $task->fresh()->status);
        }
    }

    public function test_admin_can_drag_any_task_to_any_status(): void
    {
        $admin = User::factory()->admin()->create();
        $sales = User::factory()->create();
        $task = Task::factory()->assignedTo($sales)->create([
            'created_by' => $sales->id,
            'status' => 'pending',
            'title' => 'Admin drag',
        ]);

        $this->actingAs($admin)
            ->postJson(route('tasks.board.update'), [
                'task_id' => $task->id,
                'status' => 'cancelled',
                'sort_order' => 2,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('cancelled', $task->fresh()->status);
    }

    public function test_sales_user_cannot_drag_another_users_task(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        $task = Task::factory()->assignedTo($other)->create([
            'created_by' => $other->id,
            'status' => 'pending',
        ]);

        $this->actingAs($viewer)
            ->postJson(route('tasks.board.update'), [
                'task_id' => $task->id,
                'status' => 'completed',
                'sort_order' => 1,
            ])
            ->assertForbidden();

        $this->assertSame('pending', $task->fresh()->status);
    }

    public function test_task_board_page_enables_dragging_for_sales_user(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'status' => 'pending',
            'title' => 'Visible task',
        ]);

        $response = $this->actingAs($user)->get(route('tasks.index'));

        $response->assertOk();
        $response->assertSee('data-draggable="1"', false);
        $response->assertSee('tasks-kanban', false);
        $response->assertSee('Visible task');
        $response->assertSee(route('tasks.show', $task), false);
        $response->assertSee('fa-eye', false);
    }

    public function test_sales_user_can_view_assigned_task_details(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'title' => 'Review contract details',
            'description' => 'Check payment terms',
            'status' => 'pending',
            'priority' => 'high',
        ]);

        $this->actingAs($user)
            ->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Review contract details')
            ->assertSee('Check payment terms')
            ->assertSee('Task details');
    }

    public function test_sales_user_cannot_view_another_users_task_details(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        $task = Task::factory()->assignedTo($other)->create([
            'created_by' => $other->id,
            'title' => 'Private task',
        ]);

        $this->actingAs($viewer)
            ->get(route('tasks.show', $task))
            ->assertForbidden();
    }
}
