<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;
use App\Support\CurrentCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

/**
 * @mixin Model
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('company_id') !== null) {
                return;
            }

            // Platform Super Admins intentionally have no company.
            if ($model instanceof \App\Models\User && $model->getAttribute('is_super_admin')) {
                return;
            }

            // Platform ActivityLog rows may intentionally keep company_id null.
            if (
                $model instanceof \App\Models\ActivityLog
                && array_key_exists('company_id', $model->getAttributes())
            ) {
                return;
            }

            $companyId = app(CurrentCompany::class)->id();

            if (
                $companyId === null
                && Schema::hasTable('companies')
                && Schema::hasColumn($model->getTable(), 'company_id')
            ) {
                $companyId = Company::default()?->id;
            }

            if ($companyId !== null && Schema::hasColumn($model->getTable(), 'company_id')) {
                $model->setAttribute('company_id', $companyId);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany(Builder $query, Company|int $company): Builder
    {
        $companyId = $company instanceof Company ? $company->id : $company;

        return $query->where($query->getModel()->getTable().'.company_id', $companyId);
    }

    public function scopeWithoutCompanyScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(CompanyScope::class);
    }
}
