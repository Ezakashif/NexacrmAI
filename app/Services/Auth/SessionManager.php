<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SessionManager
{
    /**
     * @return list<array{
     *     id: string,
     *     ip_address: string|null,
     *     user_agent: string|null,
     *     device_label: string,
     *     last_activity: int,
     *     last_activity_at: \Illuminate\Support\Carbon,
     *     is_current: bool
     * }>
     */
    public function listForUser(User $user, ?string $currentSessionId = null): array
    {
        $table = $this->table();

        if (! Schema::hasTable($table)) {
            return [];
        }

        $currentSessionId ??= session()->getId();

        return DB::table($table)
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($row) use ($currentSessionId) {
                $userAgent = $row->user_agent;

                return [
                    'id' => (string) $row->id,
                    'ip_address' => $row->ip_address,
                    'user_agent' => $userAgent,
                    'device_label' => $this->deviceLabel($userAgent),
                    'last_activity' => (int) $row->last_activity,
                    'last_activity_at' => \Illuminate\Support\Carbon::createFromTimestamp((int) $row->last_activity),
                    'is_current' => $currentSessionId !== null && hash_equals((string) $row->id, (string) $currentSessionId),
                ];
            })
            ->all();
    }

    public function destroyForUser(User $user, string $sessionId): bool
    {
        $table = $this->table();

        if (! Schema::hasTable($table)) {
            return false;
        }

        return DB::table($table)
            ->where('user_id', $user->id)
            ->where('id', $sessionId)
            ->delete() > 0;
    }

    public function destroyOtherSessions(User $user, ?string $exceptSessionId = null): int
    {
        $table = $this->table();

        if (! Schema::hasTable($table)) {
            return 0;
        }

        $exceptSessionId ??= session()->getId();

        $query = DB::table($table)->where('user_id', $user->id);

        if (filled($exceptSessionId)) {
            $query->where('id', '!=', $exceptSessionId);
        }

        return $query->delete();
    }

    public function destroyAllSessions(User $user): int
    {
        $table = $this->table();

        if (! Schema::hasTable($table)) {
            return 0;
        }

        return DB::table($table)
            ->where('user_id', $user->id)
            ->delete();
    }

    public function deviceLabel(?string $userAgent): string
    {
        if (! filled($userAgent)) {
            return 'Unknown device';
        }

        $browser = match (true) {
            Str::contains($userAgent, 'Edg/') => 'Edge',
            Str::contains($userAgent, 'Chrome/') && ! Str::contains($userAgent, 'Edg/') => 'Chrome',
            Str::contains($userAgent, 'Firefox/') => 'Firefox',
            Str::contains($userAgent, 'Safari/') && ! Str::contains($userAgent, 'Chrome/') => 'Safari',
            default => 'Browser',
        };

        $platform = match (true) {
            Str::contains($userAgent, 'Windows') => 'Windows',
            Str::contains($userAgent, 'Android') => 'Android',
            Str::contains($userAgent, 'iPhone') || Str::contains($userAgent, 'iPad') => 'iOS',
            Str::contains($userAgent, 'Mac OS') => 'macOS',
            Str::contains($userAgent, 'Linux') => 'Linux',
            default => 'Unknown OS',
        };

        return "{$browser} on {$platform}";
    }

    private function table(): string
    {
        return (string) config('session.table', 'sessions');
    }
}
