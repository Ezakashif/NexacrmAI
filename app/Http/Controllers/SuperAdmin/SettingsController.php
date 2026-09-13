<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UpdatePlatformSettingsRequest;
use App\Models\Plan;
use App\Services\ActivityLogger;
use App\Services\SuperAdmin\PlatformLogoProcessor;
use App\Services\SuperAdmin\PlatformSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        protected PlatformSettingsService $settings,
        protected PlatformLogoProcessor $logoProcessor,
    ) {}

    public function edit(): View
    {
        return view('superadmin.settings.edit', [
            'settings' => $this->settings->all(),
            'logoUrl' => $this->settings->logoUrl('light') ?: $this->settings->logoUrl(),
            'faviconUrl' => $this->settings->faviconUrl(),
            'plans' => Plan::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdatePlatformSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->boolean('remove_logo')) {
            $this->deleteLogo();
            $validated['platform_logo_path'] = null;
        }

        if ($request->hasFile('platform_logo')) {
            $this->ensurePublicStorageLink();
            $this->deleteLogo();
            $validated['platform_logo_path'] = $this->logoProcessor->storeProcessed(
                $request->file('platform_logo')
            );
        }

        if ($request->boolean('remove_favicon')) {
            $this->deleteFavicon();
            $validated['platform_favicon_path'] = null;
        }

        if ($request->hasFile('platform_favicon')) {
            $this->ensurePublicStorageLink();
            $this->deleteFavicon();
            $validated['platform_favicon_path'] = $request->file('platform_favicon')->store('platform-favicons', 'public');
        }

        unset($validated['platform_logo'], $validated['remove_logo'], $validated['platform_favicon'], $validated['remove_favicon']);

        $registrationEnabled = $request->has('registration_enabled')
            ? $request->boolean('registration_enabled')
            : $this->settings->getBool('registration_enabled');
        $emailVerificationRequired = $request->has('email_verification_required')
            ? $request->boolean('email_verification_required')
            : $this->settings->emailVerificationRequired();
        $maintenanceMode = $request->has('maintenance_mode')
            ? $request->boolean('maintenance_mode')
            : $this->settings->getBool('maintenance_mode');

        $this->settings->setMany([
            'platform_name' => $validated['platform_name'],
            'default_timezone' => $validated['default_timezone'],
            'default_currency' => $validated['default_currency'],
            'default_plan_id' => $validated['default_plan_id'] ?? null,
            'mail_from_name' => $validated['mail_from_name'] ?? null,
            'mail_from_address' => $validated['mail_from_address'] ?? null,
            'company_email' => $validated['company_email'] ?? null,
            'company_phone' => $validated['company_phone'] ?? null,
            'company_address' => $validated['company_address'] ?? null,
            'company_linkedin_url' => $validated['company_linkedin_url'] ?? null,
            'company_facebook_url' => $validated['company_facebook_url'] ?? null,
            'company_twitter_url' => $validated['company_twitter_url'] ?? null,
            'company_github_url' => $validated['company_github_url'] ?? null,
            'smtp_host' => $validated['smtp_host'] ?? null,
            'smtp_port' => $validated['smtp_port'] ?? null,
            'smtp_username' => $validated['smtp_username'] ?? null,
            'smtp_encryption' => $validated['smtp_encryption'] ?? null,
            'registration_enabled' => $registrationEnabled,
            'email_verification_required' => $emailVerificationRequired,
            'maintenance_mode' => $maintenanceMode,
            'trial_duration_days' => $validated['trial_duration_days'],
            'default_company_status' => $validated['default_company_status'],
            'platform_logo_path' => array_key_exists('platform_logo_path', $validated)
                ? $validated['platform_logo_path']
                : $this->settings->get('platform_logo_path'),
            'platform_favicon_path' => array_key_exists('platform_favicon_path', $validated)
                ? $validated['platform_favicon_path']
                : $this->settings->get('platform_favicon_path'),
        ]);

        $this->settings->setEncrypted('smtp_password', $validated['smtp_password'] ?? null);

        $this->syncTrialDaysOnTrialPlans((int) $validated['trial_duration_days']);

        $this->settings->applyBranding();

        ActivityLogger::log('platform.settings_updated', null, [
            'keys' => array_values(array_diff(array_keys($validated), ['smtp_password'])),
            'registration_enabled' => $registrationEnabled,
            'email_verification_required' => $emailVerificationRequired,
            'maintenance_mode' => $maintenanceMode,
        ]);

        return back()->with('success', 'Platform settings saved.');
    }

    public function announcement(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'broadcast_announcement' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->settings->setMany([
            'broadcast_announcement' => $validated['broadcast_announcement'] ?: null,
        ]);

        ActivityLogger::log('platform.announcement_updated', null, [
            'has_message' => filled($validated['broadcast_announcement'] ?? null),
        ]);

        return back()->with('success', 'Announcement updated.');
    }

    private function syncTrialDaysOnTrialPlans(int $trialDays): void
    {
        $trialDays = max(0, $trialDays);

        Plan::query()
            ->where(function ($query) {
                $query->where('is_free', true)
                    ->orWhere('trial_days', '>', 0);
            })
            ->update(['trial_days' => $trialDays]);
    }

    private function deleteLogo(): void
    {
        $path = $this->settings->get('platform_logo_path');

        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function deleteFavicon(): void
    {
        $path = $this->settings->get('platform_favicon_path');

        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function ensurePublicStorageLink(): void
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        if (file_exists($link) || is_link($link)) {
            return;
        }

        if (! is_dir($target)) {
            mkdir($target, 0755, true);
        }

        symlink($target, $link);
    }
}
