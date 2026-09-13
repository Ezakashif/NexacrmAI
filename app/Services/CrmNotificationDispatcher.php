<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CustomerCreated;
use App\Notifications\LeadAssigned;
use App\Notifications\TaskAssigned;
use App\Notifications\WebsiteLeadReceived;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class CrmNotificationDispatcher
{
    public function leadAssigned(Lead $lead, ?int $actorId): void
    {
        $this->notifyAssignee($lead->assigned_to, $lead->company_id, $actorId, new LeadAssigned($lead));
    }

    public function taskAssigned(Task $task, ?int $actorId): void
    {
        $this->notifyAssignee($task->assigned_to, $task->company_id, $actorId, new TaskAssigned($task));
    }

    public function customerCreated(Customer $customer): void
    {
        $this->activeCompanyAdmins($customer->company_id)
            ->each(fn (User $admin) => $this->send($admin, new CustomerCreated($customer)));
    }

    public function websiteLeadReceived(Lead $lead): void
    {
        $this->activeCompanyAdmins($lead->company_id)
            ->each(fn (User $admin) => $this->send($admin, new WebsiteLeadReceived($lead)));
    }

    private function notifyAssignee(?int $assigneeId, ?int $companyId, ?int $actorId, Notification $notification): void
    {
        if (! $assigneeId || ! $companyId || $assigneeId === $actorId) {
            return;
        }

        $assignee = User::withoutCompanyScope()
            ->active()
            ->where('company_id', $companyId)
            ->where('is_super_admin', false)
            ->find($assigneeId);

        if ($assignee) {
            $this->send($assignee, $notification);
        }
    }

    private function send(User $user, Notification $notification): void
    {
        if ($notification->via($user) !== []) {
            $user->notify($notification);
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function activeCompanyAdmins(?int $companyId): Collection
    {
        if (! $companyId) {
            return collect();
        }

        return User::withoutCompanyScope()
            ->active()
            ->where('company_id', $companyId)
            ->where('is_super_admin', false)
            ->whereHas('roles', fn ($query) => $query
                ->withoutCompanyScope()
                ->where('slug', 'admin')
                ->whereColumn('roles.company_id', 'users.company_id'))
            ->get();
    }
}
