<?php

namespace App\Services\Demo;

use App\Http\Requests\Demo\StartDemoRequest;
use App\Models\DemoVisit;
use Illuminate\Http\Request;

class DemoVisitService
{
    public function record(StartDemoRequest $request): DemoVisit
    {
        $anonymous = $request->isAnonymous();

        return DemoVisit::query()->create([
            'email' => $anonymous ? null : $request->validated('email'),
            'name' => $request->validated('name'),
            'company' => $request->validated('company'),
            'country' => $this->resolveCountry($request),
            'persona' => $request->validated('persona'),
            'contact_consent' => $anonymous ? false : $request->boolean('contact_consent'),
            'is_anonymous' => $anonymous,
            'status' => DemoVisit::STATUS_NEW,
            'started_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);
    }

    /**
     * Prefer Cloudflare's edge country header (already available on algoscrm.com).
     */
    public function resolveCountry(Request $request): ?string
    {
        $raw = strtoupper(trim((string) (
            $request->headers->get('CF-IPCountry')
            ?: $request->headers->get('CloudFront-Viewer-Country')
            ?: ''
        )));

        if ($raw === '' || in_array($raw, ['XX', 'T1'], true) || ! preg_match('/^[A-Z]{2}$/', $raw)) {
            return null;
        }

        return $raw;
    }
}
