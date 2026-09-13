<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\DemoEnvironment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side restrictions for the shared Northstar demo tenant.
 */
class RestrictDemoUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! DemoEnvironment::isDemoUser($user)) {
            return $next($request);
        }

        $route = $request->route()?->getName();

        if (is_string($route) && str_starts_with($route, 'superadmin.')) {
            abort(403, 'Demo accounts cannot access Super Admin.');
        }

        if ($this->isBlockedRoute($route, $request)) {
            abort(403, 'This action is disabled in the live demo.');
        }

        $target = $request->route('user');
        if ($target instanceof User && DemoEnvironment::isSeededDemoUser($target)) {
            if (in_array($route, ['users.destroy', 'users.status'], true)) {
                abort(403, 'Demo accounts cannot be deleted or disabled.');
            }

            if ($route === 'users.update' && $this->requestWouldAlterProtectedUser($request, $target)) {
                abort(403, 'Demo account credentials and roles cannot be changed.');
            }
        }

        if ($route === 'profile.update' && $user && $this->emailWouldChange($request, $user)) {
            abort(403, 'Demo account email cannot be changed.');
        }

        if ($route === 'company.settings.update' && $this->requestWouldAlterProtectedCompany($request)) {
            abort(403, 'Demo company ownership, slug, and subscription cannot be changed.');
        }

        if ($request->exists('is_super_admin')) {
            abort(403, 'This field cannot be changed.');
        }

        return $next($request);
    }

    private function isBlockedRoute(?string $route, Request $request): bool
    {
        if ($route === 'imports.store' && $request->route('type') === 'users') {
            return true;
        }

        return in_array($route, [
            'profile.destroy',
            'password.update',
            'password.confirm',
            'customers.destroy',
            'channels.store',
            'channels.destroy',
            'channels.disconnect',
            'channels.regenerate-secret',
            'channels.test',
            'channels.sync',
            'roles.store',
            'roles.update',
            'roles.destroy',
            'users.invite.store',
            'superadmin.dashboard',
        ], true);
    }

    private function emailWouldChange(Request $request, User $user): bool
    {
        if (! $request->exists('email')) {
            return false;
        }

        return strtolower((string) $request->input('email')) !== strtolower((string) $user->email);
    }

    private function requestWouldAlterProtectedUser(Request $request, User $target): bool
    {
        if ($this->emailWouldChange($request, $target)) {
            return true;
        }

        if ($request->filled('password') || $request->filled('password_confirmation')) {
            return true;
        }

        if ($request->exists('roles') || $request->exists('role_id') || $request->exists('role')) {
            return true;
        }

        if ($request->exists('status') && $request->input('status') !== $target->status) {
            return true;
        }

        if ($request->exists('company_id') || $request->exists('is_super_admin')) {
            return true;
        }

        return false;
    }

    private function requestWouldAlterProtectedCompany(Request $request): bool
    {
        return $request->exists('slug')
            || $request->exists('owner_id')
            || $request->exists('plan_id')
            || $request->exists('subscription_status')
            || $request->exists('subscription_id')
            || $request->exists('status')
            || $request->exists('company_id');
    }
}
