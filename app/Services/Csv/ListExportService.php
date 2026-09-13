<?php

namespace App\Services\Csv;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Services\CustomerListQueryService;
use App\Services\LeadListQueryService;
use App\Services\TaskListQueryService;
use App\Services\UserListQueryService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListExportService
{
    public function __construct(
        protected CsvStreamer $csv,
        protected LeadListQueryService $leads,
        protected CustomerListQueryService $customers,
        protected TaskListQueryService $tasks,
        protected UserListQueryService $users,
    ) {}

    /**
     * @param  array{search?: string|null, status?: string|null, assigned_to?: string|null, source?: string|null}  $filters
     */
    public function leads(User $user, array $filters): StreamedResponse
    {
        $query = $this->leads->query($user, $filters);

        return $this->csv->download(
            'leads-'.now()->format('Y-m-d').'.csv',
            ['Name', 'Email', 'Phone', 'Company', 'Source', 'Status', 'Assigned To', 'Created At'],
            $this->mapCursor($query, fn (Lead $lead) => [
                $lead->name,
                $lead->email,
                $lead->phone,
                $lead->company,
                $lead->source,
                $lead->status,
                $lead->assignee?->name,
                optional($lead->created_at)?->toDateTimeString(),
            ]),
        );
    }

    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     */
    public function customers(array $filters): StreamedResponse
    {
        $query = $this->customers->query($filters);

        return $this->csv->download(
            'customers-'.now()->format('Y-m-d').'.csv',
            ['Name', 'Email', 'Phone', 'Company', 'Status', 'Created At'],
            $this->mapCursor($query, fn (Customer $customer) => [
                $customer->name,
                $customer->email,
                $customer->phone,
                $customer->company_name,
                $customer->status,
                optional($customer->created_at)?->toDateTimeString(),
            ]),
        );
    }

    /**
     * @param  array{search?: string|null, status?: string|null, priority?: string|null, assigned_to?: string|null}  $filters
     */
    public function tasks(User $user, array $filters): StreamedResponse
    {
        $query = $this->tasks->query($user, $filters);

        return $this->csv->download(
            'tasks-'.now()->format('Y-m-d').'.csv',
            ['Title', 'Status', 'Priority', 'Due Date', 'Assigned To', 'Related Type', 'Related Name', 'Created At'],
            $this->mapCursor($query, function (Task $task) {
                [$relatedType, $relatedName] = $this->taskRelated($task);

                return [
                    $task->title,
                    $task->status,
                    $task->priority,
                    $task->due_date,
                    $task->assignee?->name,
                    $relatedType,
                    $relatedName,
                    optional($task->created_at)?->toDateTimeString(),
                ];
            }),
        );
    }

    /**
     * @param  array{search?: string|null, role?: string|null, status?: string|null}  $filters
     */
    public function users(array $filters): StreamedResponse
    {
        $query = $this->users->query($filters);

        return $this->csv->download(
            'users-'.now()->format('Y-m-d').'.csv',
            ['Name', 'Email', 'Role', 'Status', 'Created At'],
            $this->mapCursor($query, fn (User $user) => [
                $user->name,
                $user->email,
                $user->roles->pluck('name')->implode(', '),
                $user->status,
                optional($user->created_at)?->toDateTimeString(),
            ]),
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function taskRelated(Task $task): array
    {
        if ($task->lead_id && $task->lead) {
            return ['Lead', $task->lead->name];
        }

        if ($task->customer_id && $task->customer) {
            return ['Customer', $task->customer->name];
        }

        return ['', ''];
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param  \Illuminate\Database\Eloquent\Builder<TModel>  $query
     * @param  callable(TModel): array<int, mixed>  $mapper
     * @return \Generator<int, array<int, mixed>>
     */
    protected function mapCursor($query, callable $mapper): \Generator
    {
        foreach ($query->cursor() as $model) {
            yield $mapper($model);
        }
    }
}
