<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Task;
use App\Services\ActivityLogger;
use App\Services\CrmNotificationDispatcher;
use App\Services\CustomerListQueryService;
use App\Services\CustomerTimelineService;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerListQueryService $customerListQuery,
        protected CustomerTimelineService $customerTimeline,
        protected PlanLimitService $planLimits,
        protected CrmNotificationDispatcher $notifications,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        $filters = $request->validate($this->customerListQuery->filterRules());

        $customers = $this->customerListQuery
            ->query($filters)
            ->paginate(10)
            ->withQueryString();

        return view('customers.index', compact('customers', 'filters'));
    }

    public function create()
    {
        $this->authorize('create', Customer::class);

        return view('customers.create');
    }

    public function store(StoreCustomerRequest $request)
    {
        $validated = $request->validated();

        $this->planLimits->assertCanAddCustomer($request->user()->company);

        $customer = Customer::create([
            'created_by' => auth()->id(),
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'company_name' => $validated['company_name'] ?? null,
            'status' => 'active',
        ]);

        ActivityLogger::log('customer.created', $customer, [
            'name' => $customer->name,
        ]);

        $this->notifications->customerCreated($customer);

        return redirect()->route('customers.index')
            ->with('success', 'Customer created successfully');
    }

    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        $customer->load(['creator', 'sourceLead.assignee']);

        $tasks = Task::query()
            ->visibleTo(request()->user())
            ->where(function ($query) use ($customer) {
                $query->where('customer_id', $customer->id);

                if ($customer->source_lead_id) {
                    $query->orWhere('lead_id', $customer->source_lead_id);
                }
            })
            ->with('assignee:id,name')
            ->latest('id')
            ->limit(10)
            ->get();

        $timeline = $this->customerTimeline->forCustomer($customer, request()->user());

        return view('customers.show', compact('customer', 'tasks', 'timeline'));
    }

    public function edit(Customer $customer)
    {
        $this->authorize('update', $customer);

        return view('customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $validated = $request->validated();

        $customer->update([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'company_name' => $validated['company_name'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        ActivityLogger::log('customer.updated', $customer, [
            'name' => $customer->name,
        ]);

        return redirect()->route('customers.index')
            ->with('success', 'Customer updated successfully');
    }

    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);

        ActivityLogger::log('customer.deleted', $customer, [
            'name' => $customer->name,
        ]);

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully');
    }
}
