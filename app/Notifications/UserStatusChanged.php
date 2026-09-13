<?php

namespace App\Notifications;

use App\Mail\TemplatedMail;
use App\Models\User;
use App\Notifications\Concerns\RendersTemplatedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;
    use RendersTemplatedMail;

    public function __construct(
        public string $oldStatus,
        public string $newStatus,
        public User $changedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @return MailMessage|TemplatedMail
     */
    public function toMail(object $notifiable): MailMessage|TemplatedMail
    {
        return $this->templatedMail($notifiable, 'user_status_changed', [
            'user_name' => $notifiable->name,
            'old_status' => ucfirst($this->oldStatus),
            'new_status' => ucfirst($this->newStatus),
            'changed_by' => $this->changedBy->name,
        ]);
    }
}
