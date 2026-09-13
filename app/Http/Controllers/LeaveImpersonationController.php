<?php

namespace App\Http\Controllers;

use App\Services\SuperAdmin\ImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeaveImpersonationController extends Controller
{
    public function __construct(
        protected ImpersonationService $impersonation,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $this->impersonation->stop($request);

        return redirect()
            ->route('superadmin.dashboard')
            ->with('success', 'Returned to Super Admin.');
    }
}
