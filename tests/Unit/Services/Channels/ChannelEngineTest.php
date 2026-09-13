<?php

namespace Tests\Unit\Services\Channels;

use App\Enums\Channels\ChannelProvider;
use App\Enums\Channels\WebhookEventStatus;
use App\Jobs\Channels\ProcessChannelWebhookJob;
use App\Models\ChannelConnection;
use App\Models\ChannelWebhookEvent;
use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use App\Services\Channels\ChannelManager;
use App\Services\Channels\WebhookIngestionService;
use App\Support\CurrentCompany;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ChannelEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_generic_webhook_lead_is_ingested_queued_and_processed(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);
        app(CurrentCompany::class)->set($company);

        $connection = ChannelConnection::factory()->create([
            'company_id' => $company->id,
            'webhook_secret' => 'super-secret',
        ]);

        $payload = json_encode([
            'type' => 'lead',
            'name' => 'Casey Stone',
            'email' => 'casey@example.com',
            'phone' => '+1 555 010 9999',
            'company' => 'Stone Co',
            'notes' => 'Interested in a demo',
            'external_user_id' => 'ext_casey_1',
            'campaign' => [
                'campaign_id' => 'camp_1',
                'form_id' => 'form_9',
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha256', $payload, 'super-secret');

        $event = app(WebhookIngestionService::class)->ingest(
            company: $company,
            provider: ChannelProvider::GenericWebhook,
            payload: $payload,
            connection: $connection,
            signature: $signature,
            eventType: 'lead',
        );

        $this->assertTrue($event->signature_valid);
        $this->assertSame(WebhookEventStatus::Queued, $event->status);

        Queue::assertPushed(ProcessChannelWebhookJob::class, function (ProcessChannelWebhookJob $job) use ($event) {
            return $job->webhookEventId === $event->id;
        });

        app(ChannelManager::class)->processEvent($event->fresh());

        $lead = Lead::query()->where('email', 'casey@example.com')->first();

        $this->assertNotNull($lead);
        $this->assertSame($company->id, $lead->company_id);
        $this->assertSame($admin->id, $lead->created_by);
        $this->assertSame('website', $lead->source);
        $this->assertSame(WebhookEventStatus::Processed, $event->fresh()->status);
        $this->assertDatabaseHas('lead_channel_meta', [
            'lead_id' => $lead->id,
            'campaign_id' => 'camp_1',
            'form_id' => 'form_9',
        ]);
        $this->assertDatabaseHas('channel_contacts', [
            'lead_id' => $lead->id,
            'external_user_id' => 'ext_casey_1',
        ]);
    }

    public function test_invalid_signature_marks_event_failed_and_does_not_create_lead(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        User::factory()->admin()->create(['company_id' => $company->id]);
        app(CurrentCompany::class)->set($company);

        $connection = ChannelConnection::factory()->create([
            'company_id' => $company->id,
            'webhook_secret' => 'super-secret',
        ]);

        $payload = json_encode([
            'type' => 'lead',
            'name' => 'Bad Signature',
            'email' => 'bad-sig@example.com',
        ], JSON_THROW_ON_ERROR);

        $event = app(WebhookIngestionService::class)->ingest(
            company: $company,
            provider: ChannelProvider::GenericWebhook,
            payload: $payload,
            connection: $connection,
            signature: 'invalid',
        );

        $this->assertFalse($event->signature_valid);
        $this->assertSame(WebhookEventStatus::Failed, $event->status);
        $this->assertDatabaseMissing('leads', ['email' => 'bad-sig@example.com']);
        Queue::assertNotPushed(ProcessChannelWebhookJob::class);
    }

    public function test_message_webhook_creates_conversation_and_matches_existing_lead(): void
    {
        $company = Company::factory()->create();
        User::factory()->admin()->create(['company_id' => $company->id]);
        app(CurrentCompany::class)->set($company);

        $existing = Lead::factory()->create([
            'company_id' => $company->id,
            'email' => 'jordan@example.com',
            'phone' => '+15550123456',
            'name' => 'Jordan Lee',
        ]);

        $connection = ChannelConnection::factory()->create([
            'company_id' => $company->id,
            'webhook_secret' => null,
        ]);

        $payload = json_encode([
            'type' => 'message',
            'external_user_id' => 'wa_jordan',
            'provider_message_id' => 'wamid.ABC123',
            'email' => 'jordan@example.com',
            'body' => 'Hello from WhatsApp-like channel',
        ], JSON_THROW_ON_ERROR);

        $event = app(WebhookIngestionService::class)->ingest(
            company: $company,
            provider: ChannelProvider::GenericWebhook,
            payload: $payload,
            connection: $connection,
            dispatch: false,
        );

        $result = app(ChannelManager::class)->processEvent($event);

        $this->assertTrue($result->handled);
        $this->assertSame($existing->id, $result->lead?->id);
        $this->assertNotNull($result->conversation);
        $this->assertNotNull($result->message);
        $this->assertSame('Hello from WhatsApp-like channel', $result->message->body);
        $this->assertSame(1, Lead::query()->where('email', 'jordan@example.com')->count());
    }

    public function test_duplicate_idempotency_key_is_not_reprocessed(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        User::factory()->admin()->create(['company_id' => $company->id]);

        $payload = json_encode([
            'type' => 'lead',
            'name' => 'Dup User',
            'email' => 'dup@example.com',
        ], JSON_THROW_ON_ERROR);

        $first = app(WebhookIngestionService::class)->ingest(
            company: $company,
            provider: ChannelProvider::GenericWebhook,
            payload: $payload,
            idempotencyKey: 'idem-1',
        );

        $second = app(WebhookIngestionService::class)->ingest(
            company: $company,
            provider: ChannelProvider::GenericWebhook,
            payload: $payload,
            idempotencyKey: 'idem-1',
        );

        $this->assertSame($first->id, $second->id);
        $this->assertSame(WebhookEventStatus::Duplicate, $second->fresh()->status);
        $this->assertSame(1, ChannelWebhookEvent::withoutCompanyScope()->count());
    }

    public function test_channel_events_are_tenant_scoped(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        User::factory()->admin()->create(['company_id' => $companyA->id]);
        User::factory()->admin()->create(['company_id' => $companyB->id]);

        app(CurrentCompany::class)->set($companyA);

        ChannelWebhookEvent::factory()->create([
            'company_id' => $companyA->id,
            'payload' => json_encode(['type' => 'lead', 'name' => 'A', 'email' => 'a@example.com']),
        ]);
        ChannelWebhookEvent::factory()->create([
            'company_id' => $companyB->id,
            'payload' => json_encode(['type' => 'lead', 'name' => 'B', 'email' => 'b@example.com']),
        ]);

        $this->assertSame(1, ChannelWebhookEvent::query()->count());
        $this->assertSame(2, ChannelWebhookEvent::withoutCompanyScope()->count());
    }
}
