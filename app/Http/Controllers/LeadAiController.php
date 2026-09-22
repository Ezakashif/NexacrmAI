<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\Ai\LeadAssistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class LeadAiController extends Controller
{
    public function suggest(Request $request, Lead $lead, LeadAssistService $assist): JsonResponse
    {
        $this->authorize('aiAssist', $lead);

        if (! $assist->isAvailable()) {
            return response()->json([
                'ok' => false,
                'message' => 'AI assist is not enabled. Set AI_ENABLED=true and configure a provider API key.',
                'provider' => $assist->provider(),
            ], 503);
        }

        try {
            $suggestion = $assist->suggest($lead);
        } catch (RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
                'provider' => $assist->provider(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'AI assist failed unexpectedly. Check logs and provider credentials.',
                'provider' => $assist->provider(),
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'data' => $suggestion,
        ]);
    }
}
