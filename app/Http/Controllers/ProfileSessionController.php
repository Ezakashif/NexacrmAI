<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\Auth\SessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileSessionController extends Controller
{
    public function __construct(
        private SessionManager $sessions,
    ) {}

    public function destroy(Request $request, string $session): RedirectResponse
    {
        $user = $request->user();
        $currentId = $request->session()->getId();

        if (hash_equals($currentId, $session)) {
            return back()->withErrors([
                'sessions' => 'You cannot revoke the session you are currently using. Sign out instead.',
            ]);
        }

        $deleted = $this->sessions->destroyForUser($user, $session);

        if (! $deleted) {
            return back()->withErrors([
                'sessions' => 'That session could not be found.',
            ]);
        }

        ActivityLogger::log('session.revoked', $user, [
            'session_id' => $session,
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('status', 'session-revoked');
    }

    public function destroyOthers(Request $request): RedirectResponse
    {
        $user = $request->user();

        $count = $this->sessions->destroyOtherSessions($user, $request->session()->getId());

        ActivityLogger::log('session.revoked_others', $user, [
            'revoked_count' => $count,
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('status', 'sessions-revoked');
    }
}
