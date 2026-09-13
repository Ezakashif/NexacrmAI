<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UserNotificationPreferenceService
{
    /**
     * @return array<string, array{class: class-string, label: string, description: string}>
     */
    public function types(): array
    {
        return config('user_notification_preferences.types', []);
    }

    /**
     * @return array<string, string>
     */
    public function channels(): array
    {
        return config('user_notification_preferences.channels', []);
    }

    public function isEnabled(User $user, string $notificationType, string $channel): bool
    {
        $this->assertKnownNotificationType($notificationType);
        $this->assertKnownChannel($channel);

        return (bool) (UserNotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('notification_type', $notificationType)
            ->where('channel', $channel)
            ->value('enabled') ?? true);
    }

    /**
     * @param  array<string, array<string, bool|int|string>>  $preferences
     */
    public function update(User $user, array $preferences): void
    {
        DB::transaction(function () use ($user, $preferences): void {
            foreach ($preferences as $typeKey => $channels) {
                $type = $this->types()[$typeKey] ?? null;

                if ($type === null) {
                    throw new InvalidArgumentException('Unknown notification preference type.');
                }

                foreach ($channels as $channel => $enabled) {
                    $this->assertKnownChannel($channel);

                    $user->notificationPreferences()->updateOrCreate(
                        [
                            'notification_type' => $type['class'],
                            'channel' => $channel,
                        ],
                        ['enabled' => filter_var($enabled, FILTER_VALIDATE_BOOLEAN)],
                    );
                }
            }
        });
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function forUser(User $user): array
    {
        $stored = $user->notificationPreferences()
            ->get()
            ->keyBy(fn (UserNotificationPreference $preference) => $preference->notification_type.':'.$preference->channel);

        $preferences = [];

        foreach ($this->types() as $key => $type) {
            foreach ($this->channels() as $channel => $_label) {
                $preference = $stored->get($type['class'].':'.$channel);
                $preferences[$key][$channel] = $preference?->enabled ?? true;
            }
        }

        return $preferences;
    }

    private function assertKnownNotificationType(string $notificationType): void
    {
        if (! in_array($notificationType, array_column($this->types(), 'class'), true)) {
            throw new InvalidArgumentException('Unknown notification preference type.');
        }
    }

    private function assertKnownChannel(string $channel): void
    {
        if (! array_key_exists($channel, $this->channels())) {
            throw new InvalidArgumentException('Unknown notification preference channel.');
        }
    }
}
