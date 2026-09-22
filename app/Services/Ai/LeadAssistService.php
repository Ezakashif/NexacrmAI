<?php

namespace App\Services\Ai;

use App\Contracts\Ai\AiClient;
use App\Models\Lead;
use RuntimeException;

class LeadAssistService
{
    public function __construct(
        private readonly AiClient $client,
    ) {}

    public function isAvailable(): bool
    {
        return (bool) config('ai.enabled') && $this->client->isAvailable();
    }

    public function provider(): string
    {
        return $this->client->provider();
    }

    /**
     * @return array{summary: string, next_steps: list<string>, talk_track: string, provider: string}
     */
    public function suggest(Lead $lead): array
    {
        if (! $this->isAvailable()) {
            throw new RuntimeException('AI lead assist is not available.');
        }

        $system = <<<'PROMPT'
You are a sales coach inside NexaCRM AI. Given a lead record, respond with concise, practical guidance.
Return JSON only with keys: summary (string), next_steps (array of 3-5 short strings), talk_track (string, 2-4 sentences).
Do not invent contact details that are not provided. If context is thin, say so in summary and suggest discovery questions.
PROMPT;

        $user = $this->buildLeadContext($lead);
        $raw = $this->client->complete($system, $user);
        $parsed = $this->parseJsonPayload($raw);

        return [
            'summary' => (string) ($parsed['summary'] ?? 'No summary returned.'),
            'next_steps' => array_values(array_filter(array_map(
                static fn ($step) => is_string($step) ? trim($step) : null,
                is_array($parsed['next_steps'] ?? null) ? $parsed['next_steps'] : []
            ))),
            'talk_track' => (string) ($parsed['talk_track'] ?? ''),
            'provider' => $this->client->provider(),
        ];
    }

    private function buildLeadContext(Lead $lead): string
    {
        $lead->loadMissing(['assignee:id,name', 'activities' => fn ($q) => $q->latest('occurred_at')->limit(5)]);

        $activities = $lead->activities->map(function ($activity) {
            return sprintf(
                '- [%s] %s%s',
                optional($activity->occurred_at)->toDateString() ?? 'n/a',
                $activity->typeLabel(),
                $activity->summary ? ': '.$activity->summary : ''
            );
        })->implode("\n");

        return implode("\n", array_filter([
            'Lead name: '.$lead->name,
            'Company: '.($lead->company ?: 'n/a'),
            'Status: '.$lead->statusLabel(),
            'Source: '.($lead->source ?: 'n/a'),
            'Email: '.($lead->email ?: 'n/a'),
            'Phone: '.($lead->phone ?: 'n/a'),
            'Estimated value: '.($lead->estimated_value !== null ? (string) $lead->estimated_value : 'n/a'),
            'Follow-up date: '.($lead->follow_up_date ? (string) $lead->follow_up_date : 'n/a'),
            'Assignee: '.($lead->assignee?->name ?: 'Unassigned'),
            'Notes: '.($lead->notes ?: 'n/a'),
            'Recent activities:',
            $activities !== '' ? $activities : '- none',
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonPayload(string $raw): array
    {
        $trimmed = trim($raw);

        if (preg_match('/\{.*\}/s', $trimmed, $matches) === 1) {
            $trimmed = $matches[0];
        }

        $decoded = json_decode($trimmed, true);

        if (! is_array($decoded)) {
            return [
                'summary' => $raw,
                'next_steps' => [],
                'talk_track' => '',
            ];
        }

        return $decoded;
    }
}
