<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\SuperAdmin\ImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function __construct(
        protected ImpersonationService $impersonation,
    ) {}

    public function store(Request $request, Company $company): RedirectResponse
    {
        $this->impersonation->start($request->user(), $company, $request);

        return redirect()
            ->route('dashboard')
            ->with('success', 'You are now logged in as the company admin.');
    }
}
