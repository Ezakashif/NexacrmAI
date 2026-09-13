<?php

namespace App\Notifications;

use App\Models\Task;
use App\Services\UserNotificationPreferenceService;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification
{
    public function __construct(public Task $task) {}

    public function via(object $notifiable): array
    {
        return app(UserNotificationPreferenceService::class)->isEnabled($notifiable, self::class, 'database')
            ? ['database']
            : [];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subject' => 'Task assigned to you',
            'message' => 'You have been assigned the task '.$this->task->title.'.',
            'url' => route('tasks.show', $this->task, false),
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
        ];
    }
}
