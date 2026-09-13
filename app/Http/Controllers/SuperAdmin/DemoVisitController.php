<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\DemoVisit;
use App\Support\DemoEnvironment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DemoVisitController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'persona' => ['nullable', 'string', Rule::in(array_keys(DemoEnvironment::personas()))],
            'identity' => ['nullable', Rule::in(['identified', 'anonymous'])],
            'status' => ['nullable', Rule::in(array_keys(DemoVisit::STATUSES))],
        ]);

        $visits = DemoVisit::query()
            ->search($filters['search'] ?? null)
            ->when(filled($filters['persona'] ?? null), fn ($query) => $query->where('persona', $filters['persona']))
            ->when(($filters['identity'] ?? null) === 'identified', fn ($query) => $query->identified())
            ->when(($filters['identity'] ?? null) === 'anonymous', fn ($query) => $query->anonymous())
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.demo-visits.index', [
            'visits' => $visits,
            'filters' => $filters,
            'statuses' => DemoVisit::STATUSES,
            'personas' => DemoEnvironment::personas(),
            'newCount' => DemoVisit::query()->new()->count(),
        ]);
    }

    public function show(Request $request, DemoVisit $demoVisit): View
    {
        $demoVisit->loadMissing('reviewer:id,name,email');

        if ($demoVisit->isNew()) {
            $demoVisit->markReviewed($request->user());
            $demoVisit->refresh()->loadMissing('reviewer:id,name,email');
        }

        return view('superadmin.demo-visits.show', [
            'visit' => $demoVisit,
            'statuses' => DemoVisit::STATUSES,
        ]);
    }

    public function updateStatus(Request $request, DemoVisit $demoVisit): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(DemoVisit::STATUSES))],
        ]);

        $status = $validated['status'];

        $demoVisit->forceFill([
            'status' => $status,
            'reviewed_at' => $status === DemoVisit::STATUS_NEW ? null : ($demoVisit->reviewed_at ?? now()),
            'reviewed_by' => $status === DemoVisit::STATUS_NEW
                ? null
                : ($demoVisit->reviewed_by ?? $request->user()->id),
        ])->save();

        return back()->with('success', 'Demo visit status updated.');
    }
}
