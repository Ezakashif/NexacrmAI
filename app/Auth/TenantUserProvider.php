<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class TenantUserProvider extends EloquentUserProvider
{
    /**
     * Auth runs before tenant middleware sets CurrentCompany.
     * Always query users without CompanyScope so session/remember
     * rehydration works under production fail-closed tenancy.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  TModel|null  $model
     * @return \Illuminate\Database\Eloquent\Builder<TModel>
     */
    protected function newModelQuery($model = null)
    {
        /** @var \App\Models\User $instance */
        $instance = is_null($model) ? $this->createModel() : $model;

        $query = $instance->newQuery()->withoutCompanyScope();

        with($query, $this->queryCallback);

        return $query;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        return parent::retrieveByCredentials($credentials);
    }
}
