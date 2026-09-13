<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Demo\StartDemoRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Demo\DemoVisitService;
use App\Support\DemoEnvironment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DemoLoginController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (DemoEnvironment::isDemoUser($user)) {
            return redirect()->route('dashboard');
        }

        return view('demo.select', [
            'personas' => DemoEnvironment::personas(),
        ]);
    }

    public function store(StartDemoRequest $request, DemoVisitService $visits): RedirectResponse
    {
        $persona = $request->validated('persona');
        $email = DemoEnvironment::emailForPersona($persona);
        abort_unless(is_string($email), 404);

        $user = User::withoutCompanyScope()
            ->where('email', $email)
            ->first();

        if ($user === null || ! DemoEnvironment::isSeededDemoUser($user) || $user->status !== 'active') {
            return back()->withErrors([
                'persona' => 'The live demo is temporarily unavailable. Please try again later.',
            ])->withInput();
        }

        $visit = $visits->record($request);

        Auth::login($user);
        $request->session()->regenerate();

        ActivityLogger::log('demo.started', $user, [
            'persona' => $persona,
            'demo_visit_id' => $visit->id,
            'visitor_email' => $visit->email,
            'visitor_anonymous' => $visit->is_anonymous,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Welcome to the Algos CRM Demo. Explore the dashboard, pipeline, tasks, and reports — this workspace resets daily.');
    }
}
