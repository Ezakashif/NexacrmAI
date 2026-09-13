<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreCompanyRequest;
use App\Http\Requests\SuperAdmin\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\Plan;
use App\Services\ActivityLogger;
use App\Services\CompanyListQueryService;
use App\Services\CompanyProvisioner;
use App\Services\SuperAdmin\CompanyExportService;
use App\Services\SuperAdmin\CompanyProfileService;
use App\Services\SuperAdmin\CompanySoftDeleteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function __construct(
        protected CompanyListQueryService $listQuery,
        protected CompanyProfileService $profiles,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate($this->listQuery->filterRules());
        $filters['trashed'] = $request->boolean('trashed');

        $companies = $this->listQuery
            ->query($filters)
            ->paginate(15)
            ->withQueryString();

        return view('superadmin.companies.index', [
            'companies' => $companies,
            'filters' => $filters,
            'statuses' => Company::STATUSES,
            'subscriptionStatuses' => Company::SUBSCRIPTION_STATUSES,
            'plans' => Plan::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('superadmin.companies.create', [
            'statuses' => Company::STATUSES,
            'subscriptionStatuses' => Company::SUBSCRIPTION_STATUSES,
            'plans' => Plan::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCompanyRequest $request, CompanyProvisioner $provisioner): RedirectResponse
    {
        $validated = $request->validated();

        $result = $provisioner->provision($validated);
        $company = $result['company'];

        if ($request->hasFile('logo')) {
            $company->update([
                'logo_path' => $request->file('logo')->store('company-logos', 'public'),
            ]);
        }

        $message = 'Company created successfully.';
        if ($result['admin']) {
            $message .= ' Company admin account provisioned.';
        }

        return redirect()
            ->route('superadmin.companies.show', $company)
            ->with('success', $message);
    }

    public function show(int $company): View
    {
        $model = Company::withTrashed()->findOrFail($company);
        $profile = $this->profiles->profile($model);

        return view('superadmin.companies.show', array_merge($profile, [
            'statuses' => Company::STATUSES,
            'subscriptionStatuses' => Company::SUBSCRIPTION_STATUSES,
        ]));
    }

    public function edit(Company $company): View
    {
        $company->load(['owner:id,name,email', 'plan:id,name']);

        return view('superadmin.companies.edit', [
            'company' => $company,
            'statuses' => Company::STATUSES,
            'subscriptionStatuses' => Company::SUBSCRIPTION_STATUSES,
            'plans' => Plan::query()->active()->orderBy('name')->get(),
            'owners' => $company->users()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->boolean('remove_logo') && $company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
            $validated['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        unset($validated['logo'], $validated['remove_logo']);

        $company->update($validated);

        ActivityLogger::log('company.updated', $company, [
            'name' => $company->name,
            'slug' => $company->slug,
        ]);

        return redirect()
            ->route('superadmin.companies.show', $company)
            ->with('success', 'Company updated successfully.');
    }

    public function updateStatus(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Company::STATUSES))],
        ]);

        if ($company->isDefault() && $validated['status'] === Company::STATUS_SUSPENDED) {
            return back()->withErrors(['company' => 'The default company cannot be suspended.']);
        }

        $from = $company->status;
        $company->update(['status' => $validated['status']]);

        ActivityLogger::log('company.status_changed', $company, [
            'from' => $from,
            'to' => $validated['status'],
            'name' => $company->name,
        ]);

        $label = Company::STATUSES[$validated['status']];

        return back()->with('success', "Company marked as {$label}.");
    }

    public function destroy(Company $company, CompanySoftDeleteService $softDeletes): RedirectResponse
    {
        if ($company->isDefault()) {
            return back()->withErrors(['company' => 'The default company cannot be deleted.']);
        }

        $name = $company->name;
        $slug = $company->slug;

        ActivityLogger::log('company.deleted', $company, [
            'name' => $name,
            'slug' => $slug,
        ]);

        $softDeletes->softDelete($company);

        return redirect()
            ->route('superadmin.companies.index')
            ->with('success', "Company \"{$name}\" deleted.");
    }

    public function restore(int $company, CompanySoftDeleteService $softDeletes): RedirectResponse
    {
        $model = Company::onlyTrashed()->findOrFail($company);

        $softDeletes->restore($model);

        ActivityLogger::log('company.restored', $model, [
            'name' => $model->name,
            'slug' => $model->slug,
        ]);

        return redirect()
            ->route('superadmin.companies.show', $model)
            ->with('success', "Company \"{$model->name}\" restored.");
    }

    public function pdf(Company $company, CompanyExportService $exports): Response
    {
        return $exports->pdfShow($company);
    }
}
