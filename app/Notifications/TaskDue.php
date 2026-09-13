<?php

namespace App\Notifications;

use App\Mail\TemplatedMail;
use App\Models\Task;
use App\Notifications\Concerns\RendersTemplatedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDue extends Notification implements ShouldQueue
{
    use Queueable;
    use RendersTemplatedMail;

    public function __construct(
        public Task $task,
        public string $tier,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->channelsFromPreferences($notifiable, self::class);
    }

    /**
     * @return MailMessage|TemplatedMail
     */
    public function toMail(object $notifiable): MailMessage|TemplatedMail
    {
        $isOverdue = $this->tier === 'overdue';
        $dueDate = $this->task->due_date?->format('M j, Y') ?? 'unknown date';

        return $this->templatedMail($notifiable, 'task_reminder', [
            'user_name' => $notifiable->name,
            'task_title' => $this->task->title,
            'due_date' => $dueDate,
            'tier_label' => $isOverdue ? 'Task overdue' : 'Task due today',
            'task_url' => route('tasks.show', $this->task),
        ]);
    }

    public function toArray(object $notifiable): array
    {
        $isOverdue = $this->tier === 'overdue';
        $dueDate = $this->task->due_date?->format('M j, Y') ?? 'unknown date';

        return [
            'subject' => $isOverdue ? 'Task overdue' : 'Task due today',
            'message' => $isOverdue
                ? 'Your task '.$this->task->title.' was due on '.$dueDate.'.'
                : 'Your task '.$this->task->title.' is due today ('.$dueDate.').',
            'url' => route('tasks.show', $this->task, false),
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'due_date' => $this->task->due_date?->toDateString(),
            'tier' => $this->tier,
            'is_overdue' => $isOverdue,
        ];
    }
}
