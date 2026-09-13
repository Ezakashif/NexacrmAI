<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\PlanRequest;
use App\Models\Plan;
use App\Services\ActivityLogger;
use App\Services\SuperAdmin\PlanManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlanController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:active,inactive'],
            'type' => ['nullable', 'in:free,paid,featured,public,hidden'],
        ]);

        $plans = Plan::query()
            ->withCount(['features', 'limits', 'companies'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")))
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when(($filters['type'] ?? null) === 'free', fn ($query) => $query->where('is_free', true))
            ->when(($filters['type'] ?? null) === 'paid', fn ($query) => $query->where('is_free', false))
            ->when(($filters['type'] ?? null) === 'featured', fn ($query) => $query->where('is_featured', true))
            ->when(($filters['type'] ?? null) === 'public', fn ($query) => $query->where('is_public', true))
            ->when(($filters['type'] ?? null) === 'hidden', fn ($query) => $query->where('is_public', false))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.plans.index', compact('plans', 'filters'));
    }

    public function create(): View
    {
        return view('superadmin.plans.create', ['plan' => new Plan]);
    }

    public function store(PlanRequest $request, PlanManagementService $plans): RedirectResponse
    {
        $plan = $plans->create($request->validated(), $request->user()->id);
        ActivityLogger::log('plan.created', $plan, ['name' => $plan->name, 'slug' => $plan->slug]);

        return redirect()->route('superadmin.plans.index')->with('success', 'Subscription plan created.');
    }

    public function edit(Plan $plan): View
    {
        return view('superadmin.plans.edit', ['plan' => $plan->load(['features', 'limits'])]);
    }

    public function update(PlanRequest $request, Plan $plan, PlanManagementService $plans): RedirectResponse
    {
        $plan = $plans->update($plan, $request->validated(), $request->user()->id);
        ActivityLogger::log('plan.updated', $plan, ['name' => $plan->name, 'slug' => $plan->slug]);

        return redirect()->route('superadmin.plans.index')->with('success', 'Subscription plan updated.');
    }

    public function duplicate(Plan $plan, PlanManagementService $plans): RedirectResponse
    {
        $copy = $plans->duplicate($plan->load(['features', 'limits']), request()->user()->id);
        ActivityLogger::log('plan.duplicated', $copy, ['name' => $copy->name, 'source_plan_id' => $plan->id]);

        return redirect()->route('superadmin.plans.edit', $copy)->with('success', 'Plan duplicated as a private draft.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:activate,deactivate,delete'],
            'plan_ids' => ['required', 'array', 'min:1', 'max:100'],
            'plan_ids.*' => ['integer', 'distinct', 'exists:plans,id'],
        ]);
        $plans = Plan::query()->whereIn('id', $data['plan_ids'])->get();

        if ($data['action'] === 'delete') {
            $blocked = $plans->filter(fn (Plan $plan) => $plan->is_default || $plan->companies()->exists());
            if ($blocked->isNotEmpty()) {
                return back()->withErrors(['plan' => 'Default plans and plans assigned to companies cannot be deleted.']);
            }
            DB::transaction(function () use ($plans): void {
                $plans->each(function (Plan $plan): void {
                    $name = $plan->name;
                    $plan->delete();
                    ActivityLogger::log('plan.deleted', $plan, ['name' => $name, 'slug' => $plan->slug, 'bulk' => true]);
                });
            });
        } else {
            $active = $data['action'] === 'activate';
            if (! $active && $plans->contains(fn (Plan $plan) => $plan->is_default)) {
                return back()->withErrors(['plan' => 'The default plan cannot be deactivated.']);
            }
            DB::transaction(function () use ($plans, $active): void {
                $plans->each(function (Plan $plan) use ($active): void {
                    $plan->update(['is_active' => $active, 'updated_by' => request()->user()->id]);
                    ActivityLogger::log('plan.status_changed', $plan, ['name' => $plan->name, 'to' => $active ? 'active' : 'inactive', 'bulk' => true]);
                });
            });
        }

        return back()->with('success', Str::ucfirst($data['action']).'d '.$plans->count().' subscription plan(s).');
    }

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['name', 'slug', 'short_description', 'monthly_price', 'yearly_price', 'currency', 'billing_cycle', 'trial_days', 'is_free', 'is_featured', 'is_public', 'is_active', 'sort_order']);
            Plan::query()->orderBy('sort_order')->orderBy('id')->each(function (Plan $plan) use ($out): void {
                fputcsv($out, [$plan->name, $plan->slug, $plan->short_description, $plan->monthly_price, $plan->yearly_price, $plan->currency, $plan->billing_cycle, $plan->trial_days, (int) $plan->is_free, (int) $plan->is_featured, (int) $plan->is_public, (int) $plan->is_active, $plan->sort_order]);
            });
            fclose($out);
        }, 'subscription-plans-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function sampleImport(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['name', 'slug', 'short_description', 'monthly_price', 'yearly_price', 'currency', 'billing_cycle', 'trial_days', 'is_free', 'is_featured', 'is_public', 'is_active', 'sort_order']);
            fputcsv($out, ['Starter', 'starter', 'For small teams getting organized.', '0', '0', 'USD', 'both', '14', '1', '0', '1', '1', '1']);
            fputcsv($out, ['Professional', 'professional', 'For growing sales teams.', '79', '63', 'USD', 'both', '14', '0', '1', '1', '1', '2']);
            fclose($out);
        }, 'subscription-plans-import-sample.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request, PlanManagementService $plans): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $headers = fgetcsv($handle) ?: [];
        $headers = array_map(fn ($header) => Str::snake(trim((string) $header)), $headers);
        $created = 0;
        $errors = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) { $errors[] = 'A row has an invalid number of columns.'; continue; }
            $data = array_combine($headers, $row);
            try {
                validator($data, (new PlanRequest)->rules())->validate();
                if (Plan::withTrashed()->where('slug', $data['slug'])->exists()) { throw new \RuntimeException("Slug {$data['slug']} already exists."); }
                $plans->create($data, $request->user()->id);
                $created++;
            } catch (\Throwable $e) { $errors[] = $e->getMessage(); }
        }
        fclose($handle);
        if ($created) ActivityLogger::log('plan.imported', null, ['count' => $created]);
        return back()->with($errors ? 'warning' : 'success', "{$created} plan(s) imported.".($errors ? ' Some rows were skipped.' : ''));
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->companies()->exists()) {
            return back()->withErrors(['plan' => 'Reassign companies before deleting this plan.']);
        }

        if ($plan->is_default) {
            return back()->withErrors(['plan' => 'The default plan cannot be deleted.']);
        }

        $plan->delete();
        ActivityLogger::log('plan.deleted', $plan, ['name' => $plan->name, 'slug' => $plan->slug]);

        return redirect()->route('superadmin.plans.index')->with('success', 'Subscription plan deleted.');
    }
}
