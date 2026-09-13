@php
    $trialDays = isset($trialDays)
        ? max(1, (int) $trialDays)
        : max(1, app(\App\Services\SuperAdmin\PlatformSettingsService::class)->getInt('trial_duration_days', 14));
    $trialDuration = $trialDays.' '.\Illuminate\Support\Str::plural('day', $trialDays);
@endphp

Start {{ $trialDuration }} free trial
