<?php

namespace App\Notifications;

use App\Mail\TemplatedMail;
use App\Models\UserInvitation;
use App\Notifications\Concerns\RendersTemplatedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RendersTemplatedMail;

    public function __construct(
        public UserInvitation $invitation,
        public string $roleNames,
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
        $invitation = $this->invitation->loadMissing(['company', 'inviter']);

        return $this->templatedMail($notifiable, 'company_invitation', [
            'invitee_name' => $invitation->name,
            'invitee_email' => $invitation->email,
            'inviter_name' => $invitation->inviter?->name ?? 'A teammate',
            'company_name' => $invitation->company?->name ?? 'your workspace',
            'role_names' => $this->roleNames,
            'invitation_url' => route('invitations.accept', $invitation->token),
            'expires_at' => $invitation->expires_at?->format('M j, Y') ?? '',
        ]);
    }
}
