<?php

namespace App\Services\Marketing;

use App\Models\ContactInquiry;
use App\Models\User;
use App\Notifications\ContactInquiryReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ContactInquiryService
{
    /**
     * @param  array{name: string, email: string, company?: string|null, phone?: string|null, message: string, intent?: string|null}  $payload
     */
    public function createFromRequest(array $payload, Request $request): ContactInquiry
    {
        $inquiry = ContactInquiry::query()->create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'company' => $payload['company'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'message' => $payload['message'],
            'intent' => $payload['intent'] ?? null,
            'status' => ContactInquiry::STATUS_NEW,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000) ?: null,
        ]);

        $this->notifySuperAdmins($inquiry);

        return $inquiry;
    }

    public function notifySuperAdmins(ContactInquiry $inquiry): void
    {
        $recipients = User::withoutCompanyScope()
            ->active()
            ->where('is_super_admin', true)
            ->whereNull('company_id')
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new ContactInquiryReceived($inquiry));
    }
}