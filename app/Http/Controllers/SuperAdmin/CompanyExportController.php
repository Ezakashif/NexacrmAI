<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\CompanyListQueryService;
use App\Services\SuperAdmin\CompanyExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyExportController extends Controller
{
    public function __construct(
        protected CompanyListQueryService $listQuery,
        protected CompanyExportService $exports,
    ) {}

    public function csv(Request $request): StreamedResponse
    {
        $filters = $request->validate($this->listQuery->filterRules());

        return $this->exports->csv($filters);
    }

    public function pdf(Request $request): Response
    {
        $filters = $request->validate($this->listQuery->filterRules());

        return $this->exports->pdfList($filters);
    }
}
