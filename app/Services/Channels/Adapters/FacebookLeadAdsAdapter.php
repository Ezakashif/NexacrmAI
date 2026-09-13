<?php

namespace App\Services\Channels\Adapters;

use App\Enums\Channels\ChannelProvider;
use App\Models\ChannelConnection;
use App\Models\ChannelWebhookEvent;
use App\Services\Channels\Contracts\ChannelAdapter;
use App\Services\Channels\DTOs\ChannelProcessResult;
use App\Services\Channels\DTOs\InboundLeadDTO;
use App\Services\Channels\LeadProcessingService;
use App\Services\Channels\Meta\MetaGraphClient;
use Illuminate\Support\Arr;

class FacebookLeadAdsAdapter implements ChannelAdapter
{
    public function __construct(
        protected LeadProcessingService $leads,
        protected MetaGraphClient $graph,
    ) {}

    public function provider(): ChannelProvider
    {
        return ChannelProvider::FacebookLeadAds;
    }

    public function validateSignature(
        string $payload,
        ?string $signature,
        ?ChannelConnection $connection = null,
    ): bool {
        if (! filled($signature)) {
            return false;
        }

        $secrets = array_values(array_filter([
            config('channels.meta.app_secret'),
            $connection?->webhook_secret,
        ], fn ($secret) => filled($secret)));

        if ($secrets === []) {
            return false;
        }

        foreach ($secrets as $secret) {
            $expected = hash_hmac('sha256', $payload, (string) $secret);

            if (hash_equals('sha256='.$expected, $signature) || hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    public function process(ChannelWebhookEvent $event): ChannelProcessResult
    {
        $connection = $event->connection;

        if ($connection === null) {
            return ChannelProcessResult::ignored('Facebook Lead Ads events require a channel connection.');
        }

        if (! filled($connection->access_token)) {
            return ChannelProcessResult::ignored('Facebook page access token is missing on this connection.');
        }

        $payload = $event->decodedPayload();
        $changes = $this->extractLeadgenChanges($payload);

        if ($changes === []) {
            return ChannelProcessResult::ignored('No leadgen changes found in Meta webhook payload.');
        }

        $lastResult = null;

        foreach ($changes as $change) {
            $pageId = (string) ($change['page_id'] ?? '');
            $leadgenId = (string) ($change['leadgen_id'] ?? '');

            if ($leadgenId === '') {
                continue;
            }

            if (
                filled($connection->external_page_id)
                && $pageId !== ''
                && $pageId !== (string) $connection->external_page_id
            ) {
                continue;
            }

            $graphLead = $this->graph->fetchLead($leadgenId, (string) $connection->access_token);
            $fields = $this->mapFieldData($graphLead['field_data'] ?? []);
            $name = $this->resolveName($fields);

            if ($name === '' || (! filled($fields['email'] ?? null) && ! filled($fields['phone'] ?? null))) {
                $lastResult = ChannelProcessResult::ignored('Leadgen payload is missing name and contact details.');

                continue;
            }

            $dto = new InboundLeadDTO(
                provider: $event->provider,
                name: $name,
                email: $fields['email'] ?? null,
                phone: $fields['phone'] ?? null,
                companyName: $fields['company'] ?? null,
                notes: $this->buildNotes($fields),
                externalUserId: $pageId !== '' ? "fb_page_{$pageId}" : null,
                externalLeadId: $leadgenId,
                campaign: [
                    'page_id' => $pageId ?: $connection->external_page_id,
                    'form_id' => $change['form_id'] ?? $graphLead['form_id'] ?? null,
                    'ad_id' => $change['ad_id'] ?? $graphLead['ad_id'] ?? null,
                    'ad_name' => $graphLead['ad_name'] ?? null,
                    'adset_id' => $change['adgroup_id'] ?? $graphLead['adset_id'] ?? null,
                    'campaign_id' => $graphLead['campaign_id'] ?? null,
                    'campaign_name' => null,
                    'form_name' => null,
                ],
                meta: [
                    'leadgen' => $change,
                    'graph' => Arr::only($graphLead, [
                        'id',
                        'created_time',
                        'ad_id',
                        'ad_name',
                        'adset_id',
                        'campaign_id',
                        'form_id',
                    ]),
                    'field_data' => $graphLead['field_data'] ?? [],
                ],
            );

            $result = $this->leads->process($dto, $event);
            $lastResult = ChannelProcessResult::lead($result['lead'], $result['contact']);
        }

        return $lastResult ?? ChannelProcessResult::ignored('No matching Facebook leadgen events were processed.');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    protected function extractLeadgenChanges(array $payload): array
    {
        $changes = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $entryPageId = (string) ($entry['id'] ?? '');

            foreach ($entry['changes'] ?? [] as $change) {
                if (! is_array($change) || ($change['field'] ?? '') !== 'leadgen') {
                    continue;
                }

                $value = is_array($change['value'] ?? null) ? $change['value'] : [];
                $value['page_id'] = $value['page_id'] ?? $entryPageId;

                $changes[] = $value;
            }
        }

        return $changes;
    }

    /**
     * @param  list<array<string, mixed>>  $fieldData
     * @return array<string, string>
     */
    protected function mapFieldData(array $fieldData): array
    {
        $mapped = [];

        foreach ($fieldData as $field) {
            if (! is_array($field)) {
                continue;
            }

            $name = strtolower((string) ($field['name'] ?? ''));
            $value = $this->firstValue($field['values'] ?? []);

            if ($name === '' || $value === '') {
                continue;
            }

            $mapped[$name] = $value;
        }

        return [
            'full_name' => $mapped['full_name'] ?? null,
            'first_name' => $mapped['first_name'] ?? null,
            'last_name' => $mapped['last_name'] ?? null,
            'email' => $mapped['email'] ?? null,
            'phone' => $mapped['phone_number'] ?? $mapped['phone'] ?? null,
            'company' => $mapped['company_name'] ?? $mapped['company'] ?? null,
            'raw' => $mapped,
        ];
    }

    /**
     * @param  array<string, string|null>  $fields
     */
    protected function resolveName(array $fields): string
    {
        if (filled($fields['full_name'] ?? null)) {
            return trim((string) $fields['full_name']);
        }

        $composed = trim(implode(' ', array_filter([
            $fields['first_name'] ?? null,
            $fields['last_name'] ?? null,
        ])));

        return $composed;
    }

    /**
     * @param  array<string, string|null>  $fields
     */
    protected function buildNotes(array $fields): ?string
    {
        $raw = $fields['raw'] ?? [];
        unset($raw['full_name'], $raw['first_name'], $raw['last_name'], $raw['email'], $raw['phone_number'], $raw['phone'], $raw['company_name'], $raw['company']);

        if ($raw === []) {
            return 'Imported from Facebook Lead Ads.';
        }

        $lines = ['Imported from Facebook Lead Ads.'];

        foreach ($raw as $key => $value) {
            $lines[] = ucfirst(str_replace('_', ' ', (string) $key)).': '.$value;
        }

        return implode("\n", $lines);
    }

    /**
     * @param  mixed  $values
     */
    protected function firstValue(mixed $values): string
    {
        if (! is_array($values) || $values === []) {
            return '';
        }

        return trim((string) ($values[0] ?? ''));
    }
}
