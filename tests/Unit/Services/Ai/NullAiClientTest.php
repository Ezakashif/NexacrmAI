<?php

namespace Tests\Unit\Services\Ai;

use App\Services\Ai\LeadAssistService;
use App\Services\Ai\NullAiClient;
use RuntimeException;
use Tests\TestCase;

class NullAiClientTest extends TestCase
{
    public function test_null_client_is_unavailable_and_refuses_completions(): void
    {
        $client = new NullAiClient;

        $this->assertFalse($client->isAvailable());
        $this->assertSame('null', $client->provider());

        $this->expectException(RuntimeException::class);
        $client->complete('system', 'user');
    }

    public function test_lead_assist_reports_unavailable_when_ai_disabled(): void
    {
        config(['ai.enabled' => false]);

        $assist = new LeadAssistService(new NullAiClient);

        $this->assertFalse($assist->isAvailable());
        $this->assertSame('null', $assist->provider());
    }
}
