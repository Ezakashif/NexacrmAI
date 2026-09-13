<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanySettingsRequest;
use App\Services\ActivityLogger;
use App\Services\CompanySettingsService;
use App\Support\CurrentCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function show(CurrentCompany $currentCompany): View
    {
        $this->authorizeCompanySettingsAccess();

        $company = $currentCompany->get();
        abort_unless($company, 404);

        $company->load(['owner:id,name,email', 'plan:id,name']);

        return view('company.profile', [
            'company' => $company,
            'canEdit' => true,
        ]);
    }

    public function edit(CurrentCompany $currentCompany): View
    {
        $this->authorizeCompanySettingsAccess();

        $company = $currentCompany->get();
        abort_unless($company, 404);

        return view('company.settings.edit', [
            'company' => $company,
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function update(UpdateCompanySettingsRequest $request, CurrentCompany $currentCompany, CompanySettingsService $settings): RedirectResponse
    {
        $company = $currentCompany->get();
        abort_unless($company, 404);
        $company = $settings->update($company, $request->validated(), $request->file('logo'));

        ActivityLogger::log('company.settings_updated', $company, ['name' => $company->name]);

        return redirect()
            ->route('company.profile')
            ->with('success', 'Company settings saved.');
    }

    private function authorizeCompanySettingsAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user !== null && ($user->isAdmin() || $user->hasPermission('update.company_settings')),
            403
        );
    }
}
