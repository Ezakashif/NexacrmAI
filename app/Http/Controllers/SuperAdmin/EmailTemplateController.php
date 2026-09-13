<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\SendTestEmailTemplateRequest;
use App\Http\Requests\SuperAdmin\StoreEmailTemplateRequest;
use App\Http\Requests\SuperAdmin\UpdateEmailTemplateRequest;
use App\Models\EmailSendLog;
use App\Models\EmailTemplate;
use App\Services\ActivityLogger;
use App\Services\Email\EmailTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class EmailTemplateController extends Controller
{
    public function __construct(
        private readonly EmailTemplateService $templates,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:64'],
            'locale' => ['nullable', 'string', 'max:16'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $templates = EmailTemplate::query()
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($filters['category'] ?? null, fn ($query, string $category) => $query->where('category', $category))
            ->when($filters['locale'] ?? null, fn ($query, string $locale) => $query->where('locale', $locale))
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('category')
            ->orderBy('locale')
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.email-templates.index', [
            'templates' => $templates,
            'filters' => $filters,
            'categories' => config('email_templates.categories', []),
            'locales' => config('email_templates.locales', []),
        ]);
    }

    public function create(): View
    {
        return view('superadmin.email-templates.form', [
            'template' => new EmailTemplate([
                'locale' => config('email_templates.default_locale', 'en'),
                'is_active' => true,
                'use_branding' => true,
            ]),
            'categories' => config('email_templates.categories', []),
            'locales' => config('email_templates.locales', []),
            'mode' => 'create',
        ]);
    }

    public function store(StoreEmailTemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['category'].'-'.$data['locale']);
        $data['placeholders'] = config("email_templates.categories.{$data['category']}.placeholders", []);
        $data['updated_by'] = $request->user()->id;
        $data['version'] = 1;

        $template = EmailTemplate::query()->create($data);

        ActivityLogger::log('email_template.created', $template, [
            'category' => $template->category,
            'locale' => $template->locale,
        ]);

        return redirect()
            ->route('superadmin.email-templates.edit', $template)
            ->with('success', 'Email template created.');
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        return view('superadmin.email-templates.form', [
            'template' => $emailTemplate,
            'categories' => config('email_templates.categories', []),
            'locales' => config('email_templates.locales', []),
            'mode' => 'edit',
            'recentLogs' => EmailSendLog::query()
                ->where('email_template_id', $emailTemplate->id)
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    public function update(UpdateEmailTemplateRequest $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;
        $data['version'] = $emailTemplate->version + 1;
        $data['placeholders'] = $emailTemplate->availablePlaceholders();

        $emailTemplate->update($data);

        ActivityLogger::log('email_template.updated', $emailTemplate, [
            'category' => $emailTemplate->category,
            'locale' => $emailTemplate->locale,
            'version' => $emailTemplate->version,
        ]);

        return back()->with('success', 'Email template saved.');
    }

    public function destroy(EmailTemplate $emailTemplate): RedirectResponse
    {
        ActivityLogger::log('email_template.deleted', $emailTemplate, [
            'category' => $emailTemplate->category,
            'locale' => $emailTemplate->locale,
        ]);

        $emailTemplate->delete();

        return redirect()
            ->route('superadmin.email-templates.index')
            ->with('success', 'Email template deleted.');
    }

    public function preview(EmailTemplate $emailTemplate): View
    {
        $html = $this->templates->previewHtml($emailTemplate);

        EmailSendLog::query()->create([
            'email_template_id' => $emailTemplate->id,
            'category' => $emailTemplate->category,
            'locale' => $emailTemplate->locale,
            'to_email' => auth()->user()->email,
            'subject' => $emailTemplate->subject,
            'status' => EmailSendLog::STATUS_PREVIEW,
            'mailer' => config('mail.default'),
            'placeholders' => $this->templates->samplePlaceholders($emailTemplate->category),
            'triggered_by' => auth()->id(),
        ]);

        return view('superadmin.email-templates.preview', [
            'template' => $emailTemplate,
            'previewHtml' => $html,
        ]);
    }

    public function sendTest(SendTestEmailTemplateRequest $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $ok = $this->templates->sendTest(
            $emailTemplate,
            $request->validated('to_email'),
            $request->user(),
        );

        return back()->with(
            $ok ? 'success' : 'error',
            $ok ? 'Test email sent.' : 'Test email failed. Check mail configuration and send logs.'
        );
    }
}
