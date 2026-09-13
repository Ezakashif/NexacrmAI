<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Policies\Concerns\ChecksSameCompany;

class TaskPolicy
{
    use ChecksSameCompany;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view.tasks');
    }

    public function view(User $user, Task $task): bool
    {
        if (! $this->sameCompany($user, $task) || ! $user->hasPermission('view.tasks')) {
            return false;
        }

        return $user->canViewAllTasks() || $user->ownsTask($task);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create.tasks');
    }

    public function update(User $user, Task $task): bool
    {
        if (! $this->sameCompany($user, $task) || ! $user->hasPermission('update.tasks')) {
            return false;
        }

        return $user->ownsTask($task) || $user->canManageAnyTask();
    }

    public function changeStatus(User $user, Task $task): bool
    {
        if (! $this->view($user, $task)) {
            return false;
        }

        return $user->hasPermission('update.tasks')
            || $user->hasPermission('change_status.tasks');
    }

    public function delete(User $user, Task $task): bool
    {
        if (! $this->sameCompany($user, $task) || ! $user->hasPermission('delete.tasks')) {
            return false;
        }

        return $user->ownsTask($task) || $user->canManageAnyTask();
    }

    public function assign(User $user, Task $task): bool
    {
        if (! $this->sameCompany($user, $task) || ! $user->hasPermission('assign.tasks')) {
            return false;
        }

        return $user->canManageAnyTask() || $user->ownsTask($task);
    }
}
