<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Auth\SessionManager;
use App\Services\SuperAdmin\PlatformSettingsService;
use App\Services\UserNotificationPreferenceService;
use App\Support\DemoEnvironment;
use App\Support\EmailVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Throwable;

class ProfileController extends Controller
{
    public function __construct(
        private SessionManager $sessions,
    ) {}

    public function edit(Request $request, UserNotificationPreferenceService $preferences): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'isDemoUser' => DemoEnvironment::isDemoUser($user),
            'languages' => User::LANGUAGES,
            'notificationPreferenceTypes' => $preferences->types(),
            'notificationPreferenceChannels' => $preferences->channels(),
            'notificationPreferences' => $preferences->forUser($user),
            'sessions' => $this->sessions->listForUser($user),
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function update(ProfileUpdateRequest $request, PlatformSettingsService $settings): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        ActivityLogger::log('profile.updated', $user, [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'timezone' => $user->timezone,
            'language' => $user->language,
        ]);

        $redirect = Redirect::route('profile.edit')->with('status', 'profile-updated');

        if ($emailChanged && $settings->emailVerificationRequired() && ! $user->hasVerifiedEmail()) {
            try {
                $user->sendEmailVerificationNotification();
                $redirect->with('status', 'verification-link-sent');

                if ($preview = EmailVerification::previewUrlFor($user)) {
                    $redirect->with('verification_preview_url', $preview);
                }
            } catch (Throwable $e) {
                report($e);
                $redirect->withErrors([
                    'email' => EmailVerification::sendFailureMessage(
                        'Your email was updated, but the verification email could not be sent. Use Resend verification email.',
                        $e
                    ),
                ]);
            }
        }

        return $redirect;
    }

    public function updateNotificationPreferences(
        UpdateNotificationPreferencesRequest $request,
        UserNotificationPreferenceService $preferences,
    ): RedirectResponse {
        $preferences->update($request->user(), $request->validated('preferences'));

        return Redirect::route('profile.edit')->with('status', 'notification-preferences-updated');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $user->updatePhoto($request->file('photo'));

        ActivityLogger::log('profile.photo_updated', $user);

        return Redirect::route('profile.edit')->with('status', 'photo-updated');
    }

    public function destroyPhoto(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->removePhoto();

        ActivityLogger::log('profile.photo_removed', $user);

        return Redirect::route('profile.edit')->with('status', 'photo-removed');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        ActivityLogger::log('user.deleted', $user, [
            'name' => $user->name,
            'email' => $user->email,
        ], $user->id);

        $user->deletePhotoFile();
        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
