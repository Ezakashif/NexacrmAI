<?php

namespace App\Services\SuperAdmin;

use App\Models\Company;
use App\Services\CompanyListQueryService;
use App\Services\Csv\CsvStreamer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyExportService
{
    public function __construct(
        protected CompanyListQueryService $listQuery,
        protected CsvStreamer $csvStreamer,
    ) {}

    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     */
    public function csv(array $filters): StreamedResponse
    {
        $rows = $this->listQuery
            ->query($filters)
            ->cursor()
            ->map(fn (Company $company) => [
                $company->name,
                $company->slug,
                $company->owner?->name,
                $company->owner?->email ?? $company->email,
                $company->plan?->name,
                $company->status,
                $company->subscription_status,
                $company->users_count,
                $company->leads_count,
                $company->customers_count,
                optional($company->last_active_at)?->toDateTimeString(),
                optional($company->created_at)?->toDateTimeString(),
            ]);

        return $this->csvStreamer->download(
            'companies-'.now()->format('Y-m-d').'.csv',
            ['Name', 'Slug', 'Owner', 'Owner Email', 'Plan', 'Status', 'Subscription', 'Users', 'Leads', 'Customers', 'Last Active', 'Created At'],
            $rows,
        );
    }

    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     */
    public function pdfList(array $filters): Response
    {
        $companies = $this->listQuery->query($filters)->get();

        $pdf = Pdf::loadView('superadmin.companies.pdf.index', [
            'companies' => $companies,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('companies-'.now()->format('Y-m-d').'.pdf');
    }

    public function pdfShow(Company $company): Response
    {
        $company->loadCount(['users', 'leads', 'customers', 'tasks', 'roles']);

        $admins = $company->users()
            ->whereHas('roles', fn ($query) => $query->where('slug', 'admin'))
            ->orderBy('name')
            ->get();

        $pdf = Pdf::loadView('superadmin.companies.pdf.show', [
            'company' => $company,
            'admins' => $admins,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('company-'.$company->slug.'-'.now()->format('Y-m-d').'.pdf');
    }
}
