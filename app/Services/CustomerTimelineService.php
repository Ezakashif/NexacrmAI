<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Task;
use App\Models\User;
use App\Support\CustomerTimelineEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class CustomerTimelineService
{
    /**
     * @return Collection<int, CustomerTimelineEvent>
     */
    public function forCustomer(Customer $customer, User $viewer): Collection
    {
        $events = collect();

        $events->push($this->customerCreatedEvent($customer));

        $sourceLead = $this->resolveSourceLead($customer, $viewer);

        if ($sourceLead) {
            $events = $events
                ->merge($this->leadConvertedEvents($customer, $sourceLead))
                ->merge($this->leadAssignedEvents($sourceLead, $viewer))
                ->merge($this->leadActivityEvents($sourceLead));
        }

        $events = $events->merge($this->taskEvents($customer, $sourceLead, $viewer));

        return $events
            ->sortByDesc(fn (CustomerTimelineEvent $event) => $event->occurredAt->getTimestamp())
            ->values();
    }

    protected function customerCreatedEvent(Customer $customer): CustomerTimelineEvent
    {
        return new CustomerTimelineEvent(
            type: 'customer_created',
            label: 'Customer Created',
            occurredAt: $customer->created_at ?? now(),
            summary: sprintf('Customer record created for %s', $customer->name),
            actorName: $customer->creator?->name,
            icon: 'fas fa-user-plus',
            color: 'success',
        );
    }

    protected function resolveSourceLead(Customer $customer, User $viewer): ?Lead
    {
        $lead = $customer->relationLoaded('sourceLead')
            ? $customer->sourceLead
            : $customer->sourceLead()->with(['assignee', 'activities.user'])->first();

        if (! $lead) {
            return null;
        }

        if (! Gate::forUser($viewer)->allows('view', $lead)) {
            return null;
        }

        if (! $lead->relationLoaded('activities')) {
            $lead->load(['activities.user', 'assignee']);
        }

        return $lead;
    }

    /**
     * @return Collection<int, CustomerTimelineEvent>
     */
    protected function leadConvertedEvents(Customer $customer, Lead $lead): Collection
    {
        $log = ActivityLog::query()
            ->with('actor')
            ->where('action', 'lead.converted')
            ->where('subject_type', Lead::class)
            ->where('subject_id', $lead->id)
            ->latest('id')
            ->first();

        $occurredAt = $log?->created_at
            ?? $customer->created_at
            ?? $lead->updated_at
            ?? now();

        return collect([
            new CustomerTimelineEvent(
                type: 'lead_converted',
                label: 'Lead Converted',
                occurredAt: $occurredAt,
                summary: sprintf('Lead %s converted to customer', $lead->name),
                actorName: $log?->actor?->name,
                icon: 'fas fa-exchange-alt',
                color: 'success',
                fromLead: true,
                url: route('leads.show', $lead),
            ),
        ]);
    }

    /**
     * @return Collection<int, CustomerTimelineEvent>
     */
    protected function leadAssignedEvents(Lead $lead, User $viewer): Collection
    {
        $logs = ActivityLog::query()
            ->with('actor')
            ->where('action', 'lead.assigned')
            ->where('subject_type', Lead::class)
            ->where('subject_id', $lead->id)
            ->orderBy('created_at')
            ->get();

        if ($logs->isNotEmpty()) {
            return $logs->map(function (ActivityLog $log) use ($lead) {
                $to = $log->properties['to'] ?? $lead->assignee?->name ?? 'Unassigned';

                return new CustomerTimelineEvent(
                    type: 'lead_assigned',
                    label: 'Lead Assigned',
                    occurredAt: $log->created_at ?? now(),
                    summary: sprintf('Assigned to %s', $to),
                    actorName: $log->actor?->name,
                    icon: 'fas fa-user-check',
                    color: 'info',
                    fromLead: true,
                    url: route('leads.show', $lead),
                );
            });
        }

        if (! $lead->assigned_to) {
            return collect();
        }

        return collect([
            new CustomerTimelineEvent(
                type: 'lead_assigned',
                label: 'Lead Assigned',
                occurredAt: $lead->created_at ?? now(),
                summary: sprintf('Assigned to %s', $lead->assignee?->name ?? 'Unknown'),
                actorName: null,
                icon: 'fas fa-user-check',
                color: 'info',
                fromLead: true,
                url: route('leads.show', $lead),
            ),
        ]);
    }

    /**
     * @return Collection<int, CustomerTimelineEvent>
     */
    protected function leadActivityEvents(Lead $lead): Collection
    {
        $events = collect();

        foreach ($lead->activities as $activity) {
            $events->push($this->mapLeadActivity($activity, $lead));

            if ($activity->next_follow_up_date) {
                $events->push(new CustomerTimelineEvent(
                    type: 'follow_up',
                    label: 'Follow-up',
                    occurredAt: Carbon::parse($activity->next_follow_up_date)->startOfDay(),
                    summary: $activity->summary
                        ? 'Follow-up from: '.$activity->summary
                        : 'Follow-up scheduled',
                    actorName: $activity->user?->name,
                    icon: 'fas fa-calendar-day',
                    color: 'warning',
                    fromLead: true,
                    url: route('leads.show', $lead),
                ));
            }
        }

        if ($lead->follow_up_date && $lead->activities->whereNotNull('next_follow_up_date')->isEmpty()) {
            $events->push(new CustomerTimelineEvent(
                type: 'follow_up',
                label: 'Follow-up',
                occurredAt: Carbon::parse($lead->follow_up_date)->startOfDay(),
                summary: 'Lead follow-up date',
                actorName: $lead->assignee?->name,
                icon: 'fas fa-calendar-day',
                color: 'warning',
                fromLead: true,
                url: route('leads.show', $lead),
            ));
        }

        return $events;
    }

    protected function mapLeadActivity(LeadActivity $activity, Lead $lead): CustomerTimelineEvent
    {
        [$type, $label, $icon, $color] = match ($activity->type) {
            'call' => ['call_logged', 'Call Logged', 'fas fa-phone', 'primary'],
            'meeting' => ['meeting_scheduled', 'Meeting Scheduled', 'fas fa-calendar-check', 'warning'],
            'note' => ['notes', 'Notes', 'fas fa-sticky-note', 'secondary'],
            'whatsapp' => ['call_logged', 'WhatsApp', 'fab fa-whatsapp', 'success'],
            'email' => ['notes', 'Email', 'fas fa-envelope', 'info'],
            'status_change' => ['notes', 'Status Changed', 'fas fa-exchange-alt', 'secondary'],
            default => ['notes', $activity->typeLabel(), $activity->typeIcon(), $activity->typeColor()],
        };

        return new CustomerTimelineEvent(
            type: $type,
            label: $label,
            occurredAt: $activity->occurred_at ?? $activity->created_at ?? now(),
            summary: $activity->summary,
            actorName: $activity->user?->name,
            icon: $icon,
            color: $color,
            fromLead: true,
            url: route('leads.show', $lead),
        );
    }

    /**
     * @return Collection<int, CustomerTimelineEvent>
     */
    protected function taskEvents(Customer $customer, ?Lead $sourceLead, User $viewer): Collection
    {
        $query = Task::query()
            ->with(['assignee', 'creator'])
            ->where(function ($builder) use ($customer, $sourceLead) {
                $builder->where('customer_id', $customer->id);

                if ($sourceLead) {
                    $builder->orWhere('lead_id', $sourceLead->id);
                }
            })
            ->orderBy('created_at');

        $events = collect();

        foreach ($query->get() as $task) {
            if (! Gate::forUser($viewer)->allows('view', $task)) {
                continue;
            }

            $url = route('tasks.show', $task);
            $fromLead = $sourceLead && (int) $task->lead_id === (int) $sourceLead->id
                && (int) $task->customer_id !== (int) $customer->id;

            $events->push(new CustomerTimelineEvent(
                type: 'task_created',
                label: 'Task Created',
                occurredAt: Carbon::parse($task->created_at ?? now()),
                summary: $task->title,
                actorName: $task->creator?->name ?? $task->assignee?->name,
                icon: 'fas fa-tasks',
                color: 'primary',
                fromLead: $fromLead,
                url: $url,
            ));

            if ($task->status === 'completed' && $task->completed_at) {
                $events->push(new CustomerTimelineEvent(
                    type: 'task_completed',
                    label: 'Task Completed',
                    occurredAt: Carbon::parse($task->completed_at),
                    summary: $task->title,
                    actorName: $task->assignee?->name,
                    icon: 'fas fa-check-circle',
                    color: 'success',
                    fromLead: $fromLead,
                    url: $url,
                ));
            }
        }

        return $events;
    }
}
