<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserInvitation;
use App\Services\UserInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AcceptInvitationController extends Controller
{
    public function create(string $token): View|RedirectResponse
    {
        $invitation = UserInvitation::query()->where('token', $token)->firstOrFail();
        $invitation->markExpiredIfNeeded();
        $invitation->refresh();

        if (! $invitation->isPending()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'This invitation is no longer valid.']);
        }

        return view('auth.accept-invitation', [
            'invitation' => $invitation->load('company'),
        ]);
    }

    public function store(Request $request, string $token, UserInvitationService $invitations): RedirectResponse
    {
        $invitation = UserInvitation::query()->where('token', $token)->firstOrFail();

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $invitations->accept($invitation, $validated);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Welcome! Your account is ready.');
    }
}
