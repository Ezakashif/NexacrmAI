<?php

namespace App\Services\Csv;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvImportService
{
    public const TYPES = ['leads', 'customers', 'users'];

    public function __construct(
        protected LeadCsvImporter $leads,
        protected CustomerCsvImporter $customers,
        protected UserCsvImporter $users,
        protected CsvStreamer $csv,
    ) {}

    public function import(User $actor, string $type, UploadedFile $file): CsvImportResult
    {
        return match ($type) {
            'leads' => $this->leads->import($actor, $file),
            'customers' => $this->customers->import($actor, $file),
            'users' => $this->users->import($actor, $file),
            default => throw new InvalidArgumentException("Unsupported import type [{$type}]."),
        };
    }

    /**
     * @return list<string>
     */
    public function headersFor(string $type): array
    {
        return match ($type) {
            'leads' => LeadCsvImporter::HEADERS,
            'customers' => CustomerCsvImporter::HEADERS,
            'users' => UserCsvImporter::HEADERS,
            default => throw new InvalidArgumentException("Unsupported import type [{$type}]."),
        };
    }

    /**
     * @return list<list<string>>
     */
    public function sampleRowsFor(string $type): array
    {
        return match ($type) {
            'leads' => [[
                'Jane Doe',
                'jane@example.com',
                '+1-555-0100',
                'Acme Inc',
                'website',
                '5000',
                'Met at conference',
                '2026-08-01',
                '',
            ]],
            'customers' => [[
                'Acme Corp',
                'billing@acme.com',
                '+1-555-0100',
                'Acme Inc',
                '123 Main Street',
                'VIP account',
            ]],
            'users' => [[
                'New Rep',
                'rep@example.com',
                'Password123!',
                'sales',
                'active',
            ]],
            default => throw new InvalidArgumentException("Unsupported import type [{$type}]."),
        };
    }

    public function downloadSample(string $type): StreamedResponse
    {
        $headers = $this->headersFor($type);
        $rows = collect($this->sampleRowsFor($type));

        return $this->csv->download("{$type}-import-sample.csv", $headers, $rows);
    }

    public function permissionSlug(string $type): string
    {
        return "import.{$type}";
    }

    public function createPermissionSlug(string $type): string
    {
        return "create.{$type}";
    }

    public function indexRoute(string $type): string
    {
        return "{$type}.index";
    }

    public function label(string $type): string
    {
        return match ($type) {
            'leads' => 'Leads',
            'customers' => 'Customers',
            'users' => 'Users',
            default => ucfirst($type),
        };
    }
}
