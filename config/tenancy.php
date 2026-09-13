<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fail closed without tenant context
    |--------------------------------------------------------------------------
    |
    | When true, Eloquent queries on tenant models return no rows unless
    | CurrentCompany is set. Cross-tenant code must call withoutCompanyScope().
    | Defaults to enabled in the production environment.
    |
    */

    'fail_closed_without_context' => env('TENANCY_FAIL_CLOSED_WITHOUT_CONTEXT'),

];
