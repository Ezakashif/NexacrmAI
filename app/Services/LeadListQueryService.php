<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class LeadListQueryService
{
    /**
     * @return array<string, string>
     */
    public function filterRules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:new,contacted,qualified,proposal_sent,won,lost',
            'assigned_to' => 'nullable|string',
            'source' => 'nullable|in:'.implode(',', Lead::SOURCES),
        ];
    }

    /**
     * @param  array{search?: string|null, status?: string|null, assigned_to?: string|null, source?: string|null}  $filters
     * @return Builder<Lead>
     */
    public function query(User $user, array $filters): Builder
    {
        return Lead::visibleTo($user)
            ->with('assignee')
            ->search($filters['search'] ?? null)
            ->status($filters['status'] ?? null)
            ->when($user->canViewAllLeads(), fn (Builder $query) => $query->assignedTo($filters['assigned_to'] ?? null))
            ->source($filters['source'] ?? null)
            ->orderBy('status')
            ->orderBy('sort_order');
    }
}
