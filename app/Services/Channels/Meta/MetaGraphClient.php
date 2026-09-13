<?php

namespace App\Services\Channels\Meta;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaGraphClient
{
    /**
     * @return array<string, mixed>
     */
    public function fetchLead(string $leadgenId, string $accessToken): array
    {
        $response = Http::timeout(15)->get($this->url($leadgenId), [
            'access_token' => $accessToken,
            'fields' => 'id,created_time,field_data,ad_id,ad_name,adset_id,campaign_id,form_id',
        ]);

        try {
            $response->throw();
        } catch (RequestException $e) {
            $message = $e->response?->json('error.message') ?? $e->getMessage();

            throw new RuntimeException("Meta Graph API lead fetch failed: {$message}", 0, $e);
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchPage(string $pageId, string $accessToken): array
    {
        $response = Http::timeout(15)->get($this->url($pageId), [
            'access_token' => $accessToken,
            'fields' => 'id,name',
        ]);

        try {
            $response->throw();
        } catch (RequestException $e) {
            $message = $e->response?->json('error.message') ?? $e->getMessage();

            throw new RuntimeException("Meta Graph API page lookup failed: {$message}", 0, $e);
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchPhoneNumber(string $phoneNumberId, string $accessToken): array
    {
        $response = Http::timeout(15)->get($this->url($phoneNumberId), [
            'access_token' => $accessToken,
            'fields' => 'id,display_phone_number,verified_name,quality_rating',
        ]);

        try {
            $response->throw();
        } catch (RequestException $e) {
            $message = $e->response?->json('error.message') ?? $e->getMessage();

            throw new RuntimeException("Meta Graph API phone number lookup failed: {$message}", 0, $e);
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function sendWhatsAppText(string $phoneNumberId, string $accessToken, string $to, string $body): array
    {
        $response = Http::timeout(15)
            ->withToken($accessToken)
            ->post($this->url($phoneNumberId.'/messages'), [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $body],
            ]);

        try {
            $response->throw();
        } catch (RequestException $e) {
            $message = $e->response?->json('error.message') ?? $e->getMessage();

            throw new RuntimeException("WhatsApp send failed: {$message}", 0, $e);
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    protected function url(string $nodeId): string
    {
        $version = (string) config('channels.meta.graph_version', 'v21.0');

        return "https://graph.facebook.com/{$version}/{$nodeId}";
    }
}
