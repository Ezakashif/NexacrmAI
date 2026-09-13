<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompanySettingsService
{
    public function update(Company $company, array $data, ?UploadedFile $logo = null): Company
    {
        return DB::transaction(function () use ($company, $data, $logo): Company {
            if (($data['remove_logo'] ?? false) && $company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
                $data['logo_path'] = null;
            }

            if ($logo) {
                if ($company->logo_path) {
                    Storage::disk('public')->delete($company->logo_path);
                }
                $data['logo_path'] = $logo->store('company-logos', 'public');
            }

            $company->update(Arr::except($data, ['logo', 'remove_logo']));

            return $company->fresh();
        });
    }
}
