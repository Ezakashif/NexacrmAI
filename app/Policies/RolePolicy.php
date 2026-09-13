<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Policies\Concerns\ChecksSameCompany;

class RolePolicy
{
    use ChecksSameCompany;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view.roles');
    }

    public function view(User $user, Role $role): bool
    {
        return $this->sameCompany($user, $role)
            && $user->hasPermission('view.roles');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create.roles');
    }

    public function update(User $user, Role $role): bool
    {
        return $this->sameCompany($user, $role)
            && $user->hasPermission('update.roles');
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->sameCompany($user, $role)
            && $user->hasPermission('delete.roles');
    }
}
