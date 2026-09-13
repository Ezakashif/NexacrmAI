<?php

namespace App\Support;

use App\Models\User;

class DashboardTour
{
    /**
     * @return list<array{id: string, selector: string, title: string, description: string}>
     */
    public static function stepsFor(User $user): array
    {
        $steps = config('dashboard_tour.steps', []);

        $filtered = [];

        foreach ($steps as $step) {
            if (! is_array($step)) {
                continue;
            }

            if (filled($step['permission'] ?? null) && ! self::allows($user, (string) $step['permission'])) {
                continue;
            }

            $any = $step['permission_any'] ?? null;
            if (is_array($any) && $any !== []) {
                $allowed = false;
                foreach ($any as $permission) {
                    if (self::allows($user, (string) $permission)) {
                        $allowed = true;
                        break;
                    }
                }

                if (! $allowed) {
                    continue;
                }
            }

            $filtered[] = [
                'id' => (string) ($step['id'] ?? ''),
                'selector' => (string) ($step['selector'] ?? ''),
                'title' => (string) ($step['title'] ?? ''),
                'description' => (string) ($step['description'] ?? ''),
            ];
        }

        return array_values(array_filter(
            $filtered,
            fn (array $step) => $step['id'] !== '' && $step['selector'] !== ''
        ));
    }

    private static function allows(User $user, string $permission): bool
    {
        return match ($permission) {
            'access-reports' => $user->canAccessReports(),
            'access-search' => $user->hasAnyPermission([
                'view.leads',
                'view.customers',
                'view.tasks',
                'view.users',
            ]),
            default => $user->hasPermission($permission),
        };
    }
}
