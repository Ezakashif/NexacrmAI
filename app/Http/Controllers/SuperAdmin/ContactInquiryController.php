<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ContactInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactInquiryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'intent' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', Rule::in(array_keys(ContactInquiry::STATUSES))],
        ]);

        $inquiries = ContactInquiry::query()
            ->search($filters['search'] ?? null)
            ->when(filled($filters['intent'] ?? null), function ($query) use ($filters) {
                if (($filters['intent'] ?? null) === 'general') {
                    $query->where(function ($builder) {
                        $builder->whereNull('intent')->orWhere('intent', '')->orWhere('intent', 'general');
                    });

                    return;
                }

                $query->where('intent', $filters['intent']);
            })
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.contact-inquiries.index', [
            'inquiries' => $inquiries,
            'filters' => $filters,
            'statuses' => ContactInquiry::STATUSES,
            'newCount' => ContactInquiry::query()->new()->count(),
        ]);
    }

    public function show(Request $request, ContactInquiry $contactInquiry): View
    {
        $contactInquiry->loadMissing('reviewer:id,name,email');

        if ($contactInquiry->isNew()) {
            $contactInquiry->markReviewed($request->user());
            $contactInquiry->refresh()->loadMissing('reviewer:id,name,email');
        }

        $this->markRelatedNotificationsRead($request, $contactInquiry);

        return view('superadmin.contact-inquiries.show', [
            'inquiry' => $contactInquiry,
            'statuses' => ContactInquiry::STATUSES,
        ]);
    }

    public function updateStatus(Request $request, ContactInquiry $contactInquiry): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(ContactInquiry::STATUSES))],
        ]);

        $status = $validated['status'];

        $contactInquiry->forceFill([
            'status' => $status,
            'reviewed_at' => $status === ContactInquiry::STATUS_NEW ? null : ($contactInquiry->reviewed_at ?? now()),
            'reviewed_by' => $status === ContactInquiry::STATUS_NEW
                ? null
                : ($contactInquiry->reviewed_by ?? $request->user()->id),
        ])->save();

        return back()->with('success', 'Inquiry status updated.');
    }

    protected function markRelatedNotificationsRead(Request $request, ContactInquiry $inquiry): void
    {
        $user = $request->user();

        if (! $user) {
            return;
        }

        $user->unreadNotifications()
            ->where('type', \App\Notifications\ContactInquiryReceived::class)
            ->get()
            ->filter(fn ($notification) => (int) ($notification->data['contact_inquiry_id'] ?? 0) === $inquiry->id)
            ->each->markAsRead();
    }
}
