<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\UserInvitationService;
use App\Support\CrmValidation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserInvitationController extends Controller
{
    public function __construct(
        private readonly UserInvitationService $invitations,
    ) {}

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.invite', [
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [
                'integer',
                CrmValidation::existsInCompany('roles', 'id', $request->user()->company_id),
            ],
        ]);

        $this->invitations->invite(
            $request->user(),
            $validated['name'],
            $validated['email'],
            $validated['roles'],
        );

        return redirect()
            ->route('users.index')
            ->with('success', 'Invitation sent.');
    }
}
