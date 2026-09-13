<?php

namespace App\Policies;

use App\Models\ChannelConnection;
use App\Models\User;
use App\Policies\Concerns\ChecksSameCompany;

class ChannelConnectionPolicy
{
    use ChecksSameCompany;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view.channels');
    }

    public function view(User $user, ChannelConnection $connection): bool
    {
        return $this->sameCompany($user, $connection)
            && $user->hasPermission('view.channels');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manage.channels');
    }

    public function update(User $user, ChannelConnection $connection): bool
    {
        return $this->sameCompany($user, $connection)
            && $user->hasPermission('manage.channels');
    }

    public function delete(User $user, ChannelConnection $connection): bool
    {
        return $this->sameCompany($user, $connection)
            && $user->hasPermission('manage.channels');
    }

    public function manage(User $user, ChannelConnection $connection): bool
    {
        return $this->update($user, $connection);
    }
}
