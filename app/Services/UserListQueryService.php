<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class UserListQueryService
{
    /**
     * @return array<string, mixed>
     */
    public function filterRules(): array
    {
        $roleSlugs = Role::query()->pluck('slug')->all();

        return [
            'search' => 'nullable|string|max:255',
            'role' => ['nullable', Rule::in($roleSlugs)],
            'status' => ['nullable', Rule::in(array_keys(User::STATUSES))],
        ];
    }

    /**
     * @param  array{search?: string|null, role?: string|null, status?: string|null}  $filters
     * @return Builder<User>
     */
    public function query(array $filters): Builder
    {
        return User::query()
            ->with('roles')
            ->search($filters['search'] ?? null)
            ->role($filters['role'] ?? null)
            ->status($filters['status'] ?? null)
            ->latest();
    }
}
