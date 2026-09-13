<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Policies\Concerns\ChecksSameCompany;
use App\Support\DemoEnvironment;

class CustomerPolicy
{
    use ChecksSameCompany;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view.customers');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->sameCompany($user, $customer)
            && $user->hasPermission('view.customers');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create.customers');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->sameCompany($user, $customer)
            && $user->hasPermission('update.customers');
    }

    public function delete(User $user, Customer $customer): bool
    {
        if (DemoEnvironment::isDemoUser($user)) {
            return false;
        }

        return $this->sameCompany($user, $customer)
            && $user->hasPermission('delete.customers');
    }
}
