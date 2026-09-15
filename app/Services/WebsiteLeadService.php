<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Services\PlanLimitService;
use App\Support\CurrentCompany;
use Illuminate\Support\Facades\Validator;

class WebsiteLeadService
{
    public function __construct(
        protected CrmNotificationDispatcher $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Lead
    {
        $validated = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|required_without:phone',
            'phone' => 'nullable|string|max:50|required_without:email',
            'company' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
            'message' => 'nullable|string|max:5000',
        ])->validate();

        $company = $this->resolveTargetCompany();
        $currentCompany = app(CurrentCompany::class);
        $previousCompanyId = $currentCompany->id();
        $currentCompany->set($company);

        try {
            app(PlanLimitService::class)->assertCanAddLead($company);

            $createdById = $this->resolveCreatedByUserId($company->id);

            $sortOrder = Lead::query()->where('status', 'new')->max('sort_order') + 1;

            $lead = new Lead([
                'created_by' => $createdById,
                'assigned_to' => null,
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'company' => $validated['company'] ?? null,
                'source' => 'website',
                'status' => 'new',
                'sort_order' => $sortOrder,
                'notes' => $validated['notes'] ?? $validated['message'] ?? null,
            ]);
            $lead->company_id = $company->id;
            $lead->save();

            ActivityLogger::log('lead.created_via_website', $lead, [
                'name' => $lead->name,
            ], $createdById);

            $this->notifications->websiteLeadReceived($lead);

            $initialMessage = $validated['notes'] ?? $validated['message'] ?? null;

            if (filled($initialMessage)) {
                LeadActivity::log(
                    $lead,
                    'note',
                    $initialMessage,
                    userId: $createdById,
                );
            }

            return $lead;
        } finally {
            if ($previousCompanyId !== null) {
                $currentCompany->set($previousCompanyId);
            } else {
                $currentCompany->clear();
            }
        }
    }

    public function resolveCreatedByUserId(?int $companyId = null): int
    {
        $companyId ??= $this->resolveTargetCompany()->id;
        $email = config('website_leads.created_by_email');

        if (filled($email)) {
            $user = User::withoutCompanyScope()
                ->where('company_id', $companyId)
                ->where('email', $email)
                ->where('status', 'active')
                ->first();

            if ($user) {
                return $user->id;
            }
        }

        $admin = User::withoutCompanyScope()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->whereHas('roles', function ($query) use ($companyId) {
                $query->withoutCompanyScope()
                    ->where('slug', 'admin')
                    ->where('roles.company_id', $companyId);
            })
            ->orderBy('id')
            ->first();

        if ($admin) {
            return $admin->id;
        }

        abort(503, 'Website lead webhook has no active admin user to own new leads.');
    }

    protected function resolveTargetCompany(): Company
    {
        $email = config('website_leads.created_by_email');

        if (filled($email)) {
            $user = User::withoutCompanyScope()
                ->where('email', $email)
                ->where('status', 'active')
                ->whereNotNull('company_id')
                ->first();

            if ($user) {
                return Company::query()->findOrFail($user->company_id);
            }

            abort(503, 'WEBSITE_LEAD_CREATED_BY_EMAIL does not match an active tenant user.');
        }

        $companyIds = User::withoutCompanyScope()
            ->where('status', 'active')
            ->whereNotNull('company_id')
            ->where('is_super_admin', false)
            ->whereHas('roles', function ($query) {
                $query->withoutCompanyScope()->where('slug', 'admin');
            })
            ->pluck('company_id')
            ->unique()
            ->values();

        if ($companyIds->count() === 1) {
            return Company::query()->findOrFail($companyIds->first());
        }

        if ($companyIds->isEmpty()) {
            abort(503, 'Website lead webhook has no tenant with an active admin to attach new leads to.');
        }

        abort(503, 'Website lead webhook cannot choose a tenant. Set WEBSITE_LEAD_CREATED_BY_EMAIL to an existing tenant admin.');
    }
}
