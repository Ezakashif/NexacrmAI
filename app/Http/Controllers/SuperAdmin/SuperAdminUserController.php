<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreSuperAdminRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SuperAdminUserController extends Controller
{
    public function index(): View
    {
        $users = User::withoutCompanyScope()
            ->where('is_super_admin', true)
            ->orderBy('name')
            ->paginate(20);

        return view('superadmin.super-admins.index', [
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        return view('superadmin.super-admins.create');
    }

    public function store(StoreSuperAdminRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = new User;
        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
            'is_super_admin' => true,
        ]);
        $user->company_id = null;
        $user->save();

        ActivityLogger::log('platform.super_admin_created', $user, [
            'name' => $user->name,
            'email' => $user->email,
        ]);

        return redirect()
            ->route('superadmin.super-admins.index')
            ->with('success', 'Super Admin created successfully.');
    }
}
