<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Policies\Concerns\ChecksSameCompany;

class LeadPolicy
{
    use ChecksSameCompany;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view.leads');
    }

    public function view(User $user, Lead $lead): bool
    {
        if (! $this->sameCompany($user, $lead) || ! $user->hasPermission('view.leads')) {
            return false;
        }

        return $user->canViewAllLeads() || $user->ownsLead($lead);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create.leads');
    }

    public function update(User $user, Lead $lead): bool
    {
        if (! $this->sameCompany($user, $lead) || ! $user->hasPermission('update.leads')) {
            return false;
        }

        return $user->ownsLead($lead) || $user->canManageAnyLead();
    }

    public function delete(User $user, Lead $lead): bool
    {
        if (! $this->sameCompany($user, $lead) || ! $user->hasPermission('delete.leads')) {
            return false;
        }

        return $user->ownsLead($lead) || $user->canManageAnyLead();
    }

    public function assign(User $user, Lead $lead): bool
    {
        if (! $this->sameCompany($user, $lead) || ! $user->hasPermission('assign.leads')) {
            return false;
        }

        return $user->canManageAnyLead() || $user->ownsLead($lead);
    }

    public function convert(User $user, Lead $lead): bool
    {
        if (! $this->sameCompany($user, $lead) || ! $user->hasPermission('convert.leads')) {
            return false;
        }

        return $user->ownsLead($lead) || $user->canViewAllLeads();
    }

    public function createActivity(User $user, Lead $lead): bool
    {
        if (! $this->sameCompany($user, $lead) || ! $user->hasPermission('log.leads')) {
            return false;
        }

        return $user->ownsLead($lead) || $user->canViewAllLeads();
    }
}
