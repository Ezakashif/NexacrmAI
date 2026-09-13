<?php

namespace App\Services\SuperAdmin;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Collection;

class PlatformSearchService
{
    /**
     * @return array{companies: Collection<int, Company>, users: Collection<int, User>}
     */
    public function search(string $term, int $limit = 8): array
    {
        $term = trim($term);

        if (mb_strlen($term) < 2) {
            return [
                'companies' => collect(),
                'users' => collect(),
            ];
        }

        $like = "%{$term}%";

        $companies = Company::query()
            ->with(['owner:id,name,email', 'plan:id,name'])
            ->where(function ($query) use ($like, $term) {
                $query->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('email', 'like', $like);

                if (str_contains($term, '.')) {
                    $query->orWhere('slug', 'like', $like);
                }
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();

        $users = User::withoutCompanyScope()
            ->with(['company:id,name,slug'])
            // Soft-deleted tenants must not surface orphaned users that still
            // point at a company_id (those company pages 404).
            ->where(function ($query) {
                $query->whereNull('company_id')
                    ->orWhereHas('company');
            })
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return compact('companies', 'users');
    }
}
