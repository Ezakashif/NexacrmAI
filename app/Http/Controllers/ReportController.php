<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Services\ReportExportService;
use App\Services\ReportService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reports,
        protected ReportExportService $exports,
    ) {}

    public function index(ReportFilterRequest $request): View
    {
        $filters = $request->filters();

        return view('reports.index', $this->reports->forUser($request->user(), $filters));
    }

    public function export(ReportFilterRequest $request, string $type): StreamedResponse
    {
        abort_unless($request->user()->hasPermission('export.reports'), 403);

        $type = strtolower($type);

        abort_unless(in_array($type, ['leads', 'customers', 'tasks', 'performance'], true), 404);

        return $this->exports->download($request->user(), $type, $request->filters());
    }
}
