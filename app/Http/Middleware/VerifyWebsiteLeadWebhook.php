<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebsiteLeadWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('website_leads.webhook_secret');

        if (! filled($secret)) {
            abort(503, 'Website lead webhook is not configured.');
        }

        $token = $request->bearerToken() ?? $request->header('X-Webhook-Secret');

        if (! is_string($token) || ! hash_equals($secret, $token)) {
            abort(401, 'Invalid webhook credentials.');
        }

        return $next($request);
    }
}
