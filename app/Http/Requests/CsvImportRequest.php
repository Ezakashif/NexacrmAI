<?php

namespace App\Http\Requests;

use App\Services\Csv\CsvImportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CsvImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = (string) $this->route('type');
        $user = $this->user();

        if (! $user || ! in_array($type, CsvImportService::TYPES, true)) {
            return false;
        }

        return $user->hasPermission('import.'.$type)
            || $user->hasPermission('create.'.$type);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'csv_file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'mimetypes:text/plain,text/csv,application/csv,text/x-csv,application/vnd.ms-excel',
                'max:2048',
            ],
            'type' => ['sometimes', Rule::in(CsvImportService::TYPES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'csv_file.required' => 'Please choose a CSV file to import.',
            'csv_file.mimes' => 'The import file must be a CSV.',
            'csv_file.max' => 'The CSV file may not be larger than 2 MB.',
        ];
    }
}
