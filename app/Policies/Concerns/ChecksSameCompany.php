<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait ChecksSameCompany
{
    protected function sameCompany(User $user, Model $model): bool
    {
        return $user->company_id !== null
            && $model->company_id !== null
            && (int) $user->company_id === (int) $model->company_id;
    }
}
