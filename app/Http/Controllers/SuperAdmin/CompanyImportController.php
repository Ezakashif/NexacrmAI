<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\CompanyCsvImportRequest;
use App\Services\Csv\CompanyCsvImporter;
use App\Services\Csv\CsvStreamer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyImportController extends Controller
{
    public function create(): View
    {
        return view('superadmin.companies.import', [
            'headers' => CompanyCsvImporter::HEADERS,
            'maxRows' => 500,
        ]);
    }

    public function store(CompanyCsvImportRequest $request, CompanyCsvImporter $importer): RedirectResponse
    {
        $result = $importer->import($request->file('csv_file'));

        return redirect()
            ->route('superadmin.companies.index')
            ->with('success', $result->summaryMessage('Companies'))
            ->with('import_errors', array_slice($result->errors, 0, 25));
    }

    public function sample(CsvStreamer $streamer): StreamedResponse
    {
        return $streamer->download(
            'companies-import-sample.csv',
            CompanyCsvImporter::HEADERS,
            [[
                'Acme CRM',
                'acme',
                'active',
                'Acme Admin',
                'admin@acme.test',
                'Password123!',
            ]],
        );
    }
}
