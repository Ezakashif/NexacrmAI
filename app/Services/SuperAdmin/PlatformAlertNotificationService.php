<?php

namespace App\Services\SuperAdmin;

use App\Jobs\SendPlatformAlertJob;
use App\Models\PlatformAlertDelivery;
use App\Models\User;
use App\Notifications\PlatformAlertDetected;
use Illuminate\Support\Facades\DB;

class PlatformAlertNotificationService
{
    public function __construct(private readonly PlatformAlertService $alerts) {}

    /**
     * Queue current danger alerts and clear deduplication state for resolved alerts.
     *
     * @return int Number of jobs dispatched
     */
    public function dispatchDangerAlerts(): int
    {
        $alerts = array_values(array_filter(
            $this->alerts->alerts(),
            fn (array $alert): bool => ($alert['severity'] ?? null) === 'danger',
        ));

        $activeTypes = array_values(array_unique(array_column($alerts, 'type')));

        $resolvedDeliveries = PlatformAlertDelivery::query();

        if ($activeTypes !== []) {
            $resolvedDeliveries->whereNotIn('alert_type', $activeTypes);
        }

        $resolvedDeliveries->delete();

        $dispatched = 0;

        foreach ($alerts as $alert) {
            SendPlatformAlertJob::dispatch($alert);
            $dispatched++;
        }

        return $dispatched;
    }

    /**
     * @param  array{type: string, severity: string, title: string, message: string, meta?: array<string, mixed>}  $alert
     * @return int Number of super admins notified
     */
    public function deliver(array $alert): int
    {
        if (($alert['severity'] ?? null) !== 'danger' || ! $this->isValidAlert($alert)) {
            return 0;
        }

        $fingerprint = $this->fingerprint($alert);
        $notified = 0;

        User::withoutCompanyScope()
            ->active()
            ->where('is_super_admin', true)
            ->whereNull('company_id')
            ->orderBy('id')
            ->each(function (User $user) use ($alert, $fingerprint, &$notified): void {
                if ($this->deliverToUser($user, $alert, $fingerprint)) {
                    $notified++;
                }
            });

        return $notified;
    }

    /**
     * Deliver an alert only if it remains a current danger alert.
     *
     * @param  array{type: string, severity: string, title: string, message: string, meta?: array<string, mixed>}  $alert
     */
    public function deliverCurrent(array $alert): int
    {
        foreach ($this->alerts->alerts() as $currentAlert) {
            if (($currentAlert['severity'] ?? null) === 'danger'
                && ($currentAlert['type'] ?? null) === $alert['type']
                && $this->isValidAlert($currentAlert)
                && $this->fingerprint($currentAlert) === $this->fingerprint($alert)) {
                return $this->deliver($currentAlert);
            }
        }

        return 0;
    }

    /**
     * @param  array{type: string, severity: string, title: string, message: string, meta?: array<string, mixed>}  $alert
     */
    private function deliverToUser(User $user, array $alert, string $fingerprint): bool
    {
        return DB::transaction(function () use ($user, $alert, $fingerprint): bool {
            $delivery = PlatformAlertDelivery::query()
                ->where('user_id', $user->id)
                ->where('alert_type', $alert['type'])
                ->lockForUpdate()
                ->first();

            if ($delivery && $delivery->fingerprint === $fingerprint && $delivery->notified_at !== null) {
                return false;
            }

            $delivery ??= new PlatformAlertDelivery([
                'user_id' => $user->id,
                'alert_type' => $alert['type'],
            ]);
            $delivery->fingerprint = $fingerprint;
            $delivery->notified_at = null;
            $delivery->save();

            $notification = new PlatformAlertDetected($alert);

            if ($notification->via($user) === []) {
                // Treat an opted-out alert as processed so enabling a future
                // preference does not deliver an old operational alert.
                $delivery->forceFill(['notified_at' => now()])->save();

                return false;
            }

            $user->notifyNow($notification);
            $delivery->forceFill(['notified_at' => now()])->save();

            return true;
        });
    }

    /**
     * @param  array<string, mixed>  $alert
     */
    private function isValidAlert(array $alert): bool
    {
        return isset($alert['type'], $alert['title'], $alert['message'])
            && is_string($alert['type'])
            && is_string($alert['title'])
            && is_string($alert['message']);
    }

    /**
     * @param  array{type: string, severity: string, title: string, message: string, meta?: array<string, mixed>}  $alert
     */
    private function fingerprint(array $alert): string
    {
        $payload = $this->sortRecursively([
            'type' => $alert['type'],
            'severity' => $alert['severity'],
            'meta' => $alert['meta'] ?? [],
        ]);

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<mixed>  $value
     * @return array<mixed>
     */
    private function sortRecursively(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortRecursively($item);
            }
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
