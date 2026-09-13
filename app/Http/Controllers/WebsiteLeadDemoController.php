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

        $lead = $this->websiteLeads->create($request->all());

        return response()->json([
            'message' => 'Lead created.',
            'lead_id' => $lead->id,
        ], 201);
    }
}
