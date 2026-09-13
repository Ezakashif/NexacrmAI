<?php

namespace App\Notifications;

use App\Models\ContactInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ContactInquiryReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ContactInquiry $inquiry,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $label = $this->inquiry->isDemo() ? 'New demo request' : 'New contact inquiry';
        $from = $this->inquiry->name;
        $company = filled($this->inquiry->company) ? ' ('.$this->inquiry->company.')' : '';

        return [
            'subject' => $label,
            'message' => $from.$company.' — '.$this->inquiry->email,
            'intent' => $this->inquiry->intent,
            'contact_inquiry_id' => $this->inquiry->id,
            'url' => route('superadmin.contact-inquiries.show', $this->inquiry, false),
        ];
    }
}
