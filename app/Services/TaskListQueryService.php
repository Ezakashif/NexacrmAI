<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TaskListQueryService
{
    /**
     * @return array<string, string>
     */
    public function filterRules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|string',
        ];
    }

    /**
     * @param  array{search?: string|null, status?: string|null, priority?: string|null, assigned_to?: string|null}  $filters
     * @return Builder<Task>
     */
    public function query(User $user, array $filters): Builder
    {
        return Task::visibleTo($user)
            ->with(['assignee', 'customer', 'lead'])
            ->search($filters['search'] ?? null)
            ->status($filters['status'] ?? null)
            ->priority($filters['priority'] ?? null)
            ->when(
                $user->canViewAllTasks(),
                fn (Builder $query) => $query->assignedTo($filters['assigned_to'] ?? null)
            )
            ->orderBy('status')
            ->orderBy('sort_order');
    }
}
