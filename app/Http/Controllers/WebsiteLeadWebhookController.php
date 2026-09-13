<?php

namespace App\Http\Controllers;

use App\Services\WebsiteLeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebsiteLeadWebhookController extends Controller
{
    public function __construct(private WebsiteLeadService $websiteLeads)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $lead = $this->websiteLeads->create($request->all());

        return response()->json([
            'message' => 'Lead created.',
            'lead_id' => $lead->id,
        ], 201);
    }
}
