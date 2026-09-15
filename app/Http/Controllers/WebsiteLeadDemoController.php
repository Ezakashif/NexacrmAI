<?php

namespace App\Http\Controllers;

use App\Services\WebsiteLeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebsiteLeadDemoController extends Controller
{
    public function __construct(private WebsiteLeadService $websiteLeads)
    {
    }

    public function index(): View
    {
        return view('demo.website-lead', [
            'webhookUrl' => url('/webhooks/leads/website'),
            'webhookConfigured' => filled(config('website_leads.webhook_secret')),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! filled(config('website_leads.webhook_secret'))) {
            return response()->json([
                'message' => 'Set WEBSITE_LEAD_WEBHOOK_SECRET in your .env file first.',
            ], 503);
        }

        $previousEmail = config('website_leads.created_by_email');
        config(['website_leads.created_by_email' => $request->user()->email]);

        try {
            $lead = $this->websiteLeads->create($request->only([
                'name',
                'email',
                'phone',
                'company',
                'message',
                'notes',
            ]));
        } finally {
            config(['website_leads.created_by_email' => $previousEmail]);
        }

        return response()->json([
            'message' => 'Lead created.',
            'lead_id' => $lead->id,
        ], 201);
    }
}
