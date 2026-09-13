<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\CrmNotificationDispatcher;
use App\Services\TaskListQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TaskController extends Controller
{
    public function __construct(
        protected TaskListQueryService $taskListQuery,
        protected CrmNotificationDispatcher $notifications,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Task::class);

        $filters = $request->validate($this->taskListQuery->filterRules());

        $tasks = $this->taskListQuery->query($request->user(), $filters)
            ->limit(Task::BOARD_CARD_LIMIT + 1)
            ->get();

        $boardTruncated = $tasks->count() > Task::BOARD_CARD_LIMIT;
        if ($boardTruncated) {
            $tasks = $tasks->take(Task::BOARD_CARD_LIMIT);
        }

        $statuses = Task::STATUSES;
        $priorities = Task::PRIORITIES;

        $users = $request->user()->canViewAllTasks()
            ? User::active()->orderBy('name')->get()
            : collect();

        return view('tasks.index', compact('tasks', 'statuses', 'priorities', 'filters', 'users', 'boardTruncated'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Task::class);

        $user = $request->user();

        $users = $user->canAssignTasks()
            ? User::active()->orderBy('name')->get()
            : collect();

        $customers = $this->customersForSelect($user);

        return view('tasks.create', compact('users', 'customers'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Task::class);

        $user = $request->user();

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:'.implode(',', array_keys(Task::PRIORITIES)),
            'due_date' => 'nullable|date',
            'customer_id' => ['nullable', \App\Support\CrmValidation::existsInCompany('customers', 'id', $user->company_id)],
        ];

        if ($user->canAssignTasks()) {
            $rules['assigned_to'] = ['required', \App\Support\CrmValidation::existsInCompany('users', 'id', $user->company_id)];
        }

        $validated = $request->validate($rules);

        if (! empty($validated['customer_id'])) {
            $this->authorize('view', Customer::findOrFail($validated['customer_id']));
        }

        $task = Task::create([
            'created_by' => $user->id,
            'assigned_to' => $user->canAssignTasks()
                ? $validated['assigned_to']
                : $user->id,
            'customer_id' => $validated['customer_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'],
            'status' => 'pending',
            'due_date' => $validated['due_date'] ?? null,
        ]);

        ActivityLogger::log('task.created', $task, [
            'title' => $task->title,
        ]);

        ActivityLogger::log('task.assigned', $task, [
            'title' => $task->title,
            'from_user_id' => null,
            'to_user_id' => $task->assigned_to,
        ]);

        $this->notifications->taskAssigned($task, $user->id);

        return redirect()->route('tasks.index')
            ->with('success', 'Task created successfully');
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);

        $task->load(['assignee', 'creator', 'customer', 'lead']);

        return view('tasks.show', compact('task'));
    }

    public function edit(Task $task)
    {
        $this->authorize('update', $task);

        $user = auth()->user();

        $task->loadMissing('assignee');

        $users = ($user->canViewAllTasks() || $user->can('assign', $task))
            ? User::active()->orderBy('name')->get()
            : collect();

        $customers = $this->customersForSelect($user);

        return view('tasks.edit', compact('task', 'users', 'customers'));
    }

    public function update(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:'.implode(',', array_keys(Task::PRIORITIES)),
            'status' => 'required|in:'.implode(',', array_keys(Task::STATUSES)),
            'due_date' => 'nullable|date',
            'customer_id' => ['nullable', \App\Support\CrmValidation::existsInCompany('customers', 'id', auth()->user()->company_id)],
        ];

        if (auth()->user()->can('assign', $task)) {
            $rules['assigned_to'] = ['required', \App\Support\CrmValidation::existsInCompany('users', 'id', auth()->user()->company_id)];
        }

        $validated = $request->validate($rules);

        if (! empty($validated['customer_id'])) {
            $this->authorize('view', Customer::findOrFail($validated['customer_id']));
        }

        $previousStatus = $task->status;
        $previousAssigneeId = $task->assigned_to;

        $task->fill($validated);

        if ($validated['status'] === 'completed') {
            $task->completed_at = $task->completed_at ?? now();
        } else {
            $task->completed_at = null;
        }

        $task->save();

        ActivityLogger::log('task.updated', $task, [
            'title' => $task->title,
        ]);

        if ($previousStatus !== $task->status) {
            ActivityLogger::log('task.status_changed', $task, [
                'from' => $previousStatus,
                'to' => $task->status,
            ]);
        }

        if (array_key_exists('assigned_to', $validated)
            && (int) $previousAssigneeId !== (int) $task->assigned_to) {
            ActivityLogger::log('task.assigned', $task, [
                'title' => $task->title,
                'from_user_id' => $previousAssigneeId,
                'to_user_id' => $task->assigned_to,
            ]);

            $this->notifications->taskAssigned($task, $request->user()->id);
        }

        return redirect()->route('tasks.index')
            ->with('success', 'Task updated successfully');
    }

    public function updateBoard(Request $request)
    {
        $request->validate([
            'task_id' => ['required', \App\Support\CrmValidation::existsInCompany('tasks', 'id', $request->user()->company_id)],
            'status' => 'required|in:'.implode(',', array_keys(Task::STATUSES)),
            'sort_order' => 'required|integer|min:0',
        ]);

        $task = Task::findOrFail($request->task_id);

        $this->authorize('changeStatus', $task);

        $previousStatus = $task->status;

        $task->status = $request->status;
        $task->sort_order = $request->sort_order;
        $task->completed_at = $request->status === 'completed' ? now() : null;
        $task->save();

        if ($previousStatus !== $task->status) {
            ActivityLogger::log('task.status_changed', $task, [
                'from' => $previousStatus,
                'to' => $task->status,
            ]);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        ActivityLogger::log('task.deleted', $task, [
            'title' => $task->title,
        ]);

        $task->delete();

        return redirect()->route('tasks.index')
            ->with('success', 'Task deleted successfully');
    }

    public function changeStatus(Request $request, Task $task)
    {
        $this->authorize('changeStatus', $task);

        $request->validate([
            'status' => 'required|in:'.implode(',', array_keys(Task::STATUSES)),
        ]);

        $previousStatus = $task->status;

        $task->update([
            'status' => $request->status,
            'completed_at' => $request->status === 'completed' ? now() : null,
        ]);

        if ($previousStatus !== $request->status) {
            ActivityLogger::log('task.status_changed', $task, [
                'from' => $previousStatus,
                'to' => $request->status,
            ]);
        }

        return back()->with('success', 'Task status updated');
    }

    /**
     * @return Collection<int, Customer>
     */
    protected function customersForSelect(User $user): Collection
    {
        if (! $user->can('viewAny', Customer::class)) {
            return collect();
        }

        return Customer::query()
            ->orderBy('name')
            ->get(['id', 'name', 'company_name', 'email']);
    }
}
