<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

class CustomerListQueryService
{
    /**
     * @return array<string, string>
     */
    public function filterRules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return Builder<Customer>
     */
    public function query(array $filters): Builder
    {
        return Customer::query()
            ->search($filters['search'] ?? null)
            ->status($filters['status'] ?? null)
            ->latest();
    }
}
