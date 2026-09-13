<?php

namespace App\Models\Scopes;

use App\Support\CurrentCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $companyId = app(CurrentCompany::class)->id();

        if ($companyId === null) {
            // Fail closed in production (and when explicitly enabled) so missing
            // tenant context cannot leak cross-company rows. Callers that need
            // unscoped access must use withoutCompanyScope().
            if ($this->shouldFailClosed()) {
                $builder->whereRaw('0 = 1');
            }

            return;
        }

        $builder->where($model->getTable().'.company_id', $companyId);
    }

    private function shouldFailClosed(): bool
    {
        $configured = config('tenancy.fail_closed_without_context');

        if ($configured === null) {
            return app()->isProduction();
        }

        return filter_var($configured, FILTER_VALIDATE_BOOLEAN);
    }
}
