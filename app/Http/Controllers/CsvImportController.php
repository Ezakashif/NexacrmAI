<?php

namespace App\Http\Controllers;

use App\Http\Requests\CsvImportRequest;
use App\Services\Csv\CsvImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CsvImportController extends Controller
{
    public function __construct(
        protected CsvImportService $imports,
    ) {}

    public function create(string $type): View
    {
        $this->authorizeType($type);

        return view('imports.form', [
            'type' => $type,
            'label' => $this->imports->label($type),
            'headers' => $this->imports->headersFor($type),
            'indexRoute' => $this->imports->indexRoute($type),
            'maxRows' => \App\Services\Csv\CsvReader::MAX_ROWS,
        ]);
    }

    public function store(CsvImportRequest $request, string $type): RedirectResponse
    {
        $this->authorizeType($type);

        try {
            $result = $this->imports->import(
                $request->user(),
                $type,
                $request->file('csv_file'),
            );
        } catch (Throwable $exception) {
            return back()
                ->withInput()
                ->withErrors(['csv_file' => $exception->getMessage()]);
        }

        return redirect()
            ->route($this->imports->indexRoute($type))
            ->with('success', $result->summaryMessage($this->imports->label($type)))
            ->with('import_errors', array_slice($result->errors, 0, 25));
    }

    public function sample(string $type): StreamedResponse
    {
        $this->authorizeType($type);

        return $this->imports->downloadSample($type);
    }

    protected function authorizeType(string $type): void
    {
        abort_unless(in_array($type, CsvImportService::TYPES, true), 404);

        $user = auth()->user();

        abort_unless(
            $user && (
                $user->hasPermission('import.'.$type)
                || $user->hasPermission($this->imports->createPermissionSlug($type))
            ),
            403
        );
    }
}
