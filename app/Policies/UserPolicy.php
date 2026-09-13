<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksSameCompany;
use App\Support\DemoEnvironment;

class UserPolicy
{
    use ChecksSameCompany;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view.users');
    }

    public function view(User $user, User $model): bool
    {
        return $this->sameCompany($user, $model)
            && $user->hasPermission('view.users');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create.users');
    }

    public function update(User $user, User $model): bool
    {
        return $this->sameCompany($user, $model)
            && $user->hasPermission('update.users');
    }

    public function delete(User $user, User $model): bool
    {
        if (DemoEnvironment::isSeededDemoUser($model)) {
            return false;
        }

        return $this->sameCompany($user, $model)
            && $user->hasPermission('delete.users');
    }
}
