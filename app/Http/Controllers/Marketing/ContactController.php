<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketing\ContactRequest;
use App\Mail\Marketing\ContactInquiryMail;
use App\Services\Marketing\ContactInquiryService;
use App\Services\SuperAdmin\PlatformSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(): View
    {
        return view('marketing.contact', [
            'intent' => request()->query('intent'),
            'faqs' => array_merge(
                config('marketing.home.faqs', []),
                config('marketing.pricing.faqs', []),
            ),
        ]);
    }

    public function store(
        ContactRequest $request,
        PlatformSettingsService $settings,
        ContactInquiryService $inquiries,
    ): RedirectResponse {
        $payload = $request->safe()->only([
            'name',
            'email',
            'company',
            'phone',
            'message',
            'intent',
        ]);

        $inquiry = $inquiries->createFromRequest($payload, $request);

        Log::info('marketing.contact.inquiry', [
            'id' => $inquiry->id,
            'email' => $inquiry->email,
            'intent' => $inquiry->intent,
        ]);

        $to = filled($settings->get('company_email'))
            ? (string) $settings->get('company_email')
            : (string) config('marketing.contact.email');

        Mail::to($to)->send(new ContactInquiryMail($payload));

        return redirect()
            ->route('marketing.contact', array_filter([
                'intent' => $payload['intent'] ?? null,
            ]))
            ->with('status', 'Thanks—your message is on its way. We’ll get back to you shortly.');
    }
}
