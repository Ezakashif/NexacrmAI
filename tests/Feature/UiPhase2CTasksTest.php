<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiPhase2CTasksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_tasks_index_uses_shared_header_and_empty_state(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('crm-page-header', false)
            ->assertSee('No tasks yet')
            ->assertSee('Add task')
            ->assertSee('crm-kanban', false)
            ->assertSee('crm-kanban-stages', false);
    }

    public function test_tasks_create_edit_show_use_shared_patterns(): void
    {
        $user = User::factory()->admin()->create();
        $task = Task::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'title' => 'Polish board cards',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('tasks.create'))
            ->assertOk()
            ->assertSee('crm-form-section', false)
            ->assertSee('Create task');

        $this->actingAs($user)
            ->get(route('tasks.edit', $task))
            ->assertOk()
            ->assertSee('Edit task')
            ->assertSee('data-crm-confirm', false)
            ->assertSee('Delete task');

        $this->actingAs($user)
            ->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Polish board cards')
            ->assertSee('Task details')
            ->assertSee('data-crm-confirm', false);
    }
}
