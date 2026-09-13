<?php

namespace App\Services\SuperAdmin;

use App\Models\Company;
use App\Models\ImpersonationLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ImpersonationService
{
    public const SESSION_IMPERSONATOR_ID = 'impersonator_id';

    public const SESSION_IMPERSONATION_LOG_ID = 'impersonation_log_id';

    public function start(User $superAdmin, Company $company, Request $request): User
    {
        if (! $superAdmin->isSuperAdmin()) {
            abort(Response::HTTP_FORBIDDEN, 'Only Super Admins can impersonate.');
        }

        if ($request->session()->has(self::SESSION_IMPERSONATOR_ID)) {
            throw ValidationException::withMessages([
                'impersonation' => 'You are already impersonating a user. Return to Super Admin first.',
            ]);
        }

        $target = $company->users()
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->where('slug', 'admin'))
            ->orderBy('id')
            ->first();

        if (! $target) {
            throw ValidationException::withMessages([
                'impersonation' => 'This company has no active admin user to login as.',
            ]);
        }

        if (! $company->isActive()) {
            throw ValidationException::withMessages([
                'impersonation' => 'Cannot login to a suspended company.',
            ]);
        }

        $log = ImpersonationLog::query()->create([
            'super_admin_id' => $superAdmin->id,
            'target_user_id' => $target->id,
            'company_id' => $company->id,
            'ip_address' => $request->ip(),
            'started_at' => now(),
        ]);

        ActivityLogger::log('impersonation.started', $target, [
            'super_admin_id' => $superAdmin->id,
            'super_admin_email' => $superAdmin->email,
            'company_id' => $company->id,
            'company_name' => $company->name,
            'impersonation_log_id' => $log->id,
        ], $superAdmin->id);

        Auth::login($target);
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_IMPERSONATOR_ID, $superAdmin->id);
        $request->session()->put(self::SESSION_IMPERSONATION_LOG_ID, $log->id);

        return $target;
    }

    public function stop(Request $request): User
    {
        $impersonatorId = $request->session()->get(self::SESSION_IMPERSONATOR_ID);
        $logId = $request->session()->get(self::SESSION_IMPERSONATION_LOG_ID);
        $current = $request->user();

        if (! $impersonatorId) {
            throw ValidationException::withMessages([
                'impersonation' => 'You are not currently impersonating a user.',
            ]);
        }

        $superAdmin = User::withoutCompanyScope()
            ->whereKey($impersonatorId)
            ->where('is_super_admin', true)
            ->first();

        if (! $superAdmin) {
            $request->session()->forget([self::SESSION_IMPERSONATOR_ID, self::SESSION_IMPERSONATION_LOG_ID]);
            Auth::logout();

            throw ValidationException::withMessages([
                'impersonation' => 'The original Super Admin account could not be restored.',
            ]);
        }

        if ($logId) {
            ImpersonationLog::query()->whereKey($logId)->update(['ended_at' => now()]);
        }

        ActivityLogger::log('impersonation.ended', $current, [
            'super_admin_id' => $superAdmin->id,
            'target_user_id' => $current?->id,
            'company_id' => $current?->company_id,
            'impersonation_log_id' => $logId,
        ], $superAdmin->id);

        Auth::login($superAdmin);
        $request->session()->regenerate();
        $request->session()->forget([self::SESSION_IMPERSONATOR_ID, self::SESSION_IMPERSONATION_LOG_ID]);

        return $superAdmin;
    }

    public function isImpersonating(?Request $request = null): bool
    {
        $request ??= request();

        return $request->hasSession()
            && $request->session()->has(self::SESSION_IMPERSONATOR_ID);
    }
}
