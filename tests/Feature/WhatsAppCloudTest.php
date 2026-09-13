<?php

namespace Tests\Feature;

use App\Enums\Channels\ChannelConnectionStatus;
use App\Enums\Channels\ChannelProvider;
use App\Enums\Channels\WebhookEventStatus;
use App\Models\ChannelConnection;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Lead;
use App\Models\User;
use App\Services\Channels\ChannelManager;
use App\Services\Channels\WebhookIngestionService;
use App\Support\CurrentCompany;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppCloudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        config(['channels.meta.app_secret' => 'meta-app-secret']);
        config(['channels.meta.graph_version' => 'v21.0']);
    }

    public function test_whatsapp_webhook_verification_returns_challenge(): void
    {
        $admin = User::factory()->admin()->create();
        $connection = ChannelConnection::factory()->create([
            'company_id' => $admin->company_id,
            'provider' => ChannelProvider::WhatsAppCloud,
            'verify_token' => 'wa-verify-token',
            'external_page_id' => 'phone_123',
            'access_token' => 'wa-token',
        ]);

        $this->get(route('webhooks.channels.inbound', $connection->uuid).'?'.http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'wa-verify-token',
            'hub.challenge' => 'challenge-wa',
        ]))
            ->assertOk()
            ->assertSee('challenge-wa');
    }

    public function test_whatsapp_inbound_message_creates_lead_and_conversation(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);
        app(CurrentCompany::class)->set($company);

        $connection = ChannelConnection::factory()->create([
            'company_id' => $company->id,
            'provider' => ChannelProvider::WhatsAppCloud,
            'external_page_id' => 'phone_123',
            'access_token' => 'wa-access-token',
            'status' => ChannelConnectionStatus::Connected,
        ]);

        $payload = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => 'waba_1',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'display_phone_number' => '15550001111',
                            'phone_number_id' => 'phone_123',
                        ],
                        'contacts' => [[
                            'profile' => ['name' => 'Sam Rivera'],
                            'wa_id' => '15559876543',
                        ]],
                        'messages' => [[
                            'from' => '15559876543',
                            'id' => 'wamid.ABC123',
                            'timestamp' => '1710000000',
                            'type' => 'text',
                            'text' => ['body' => 'Hi, I want a demo'],
                        ]],
                    ],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);

        $signature = 'sha256='.hash_hmac('sha256', $payload, 'meta-app-secret');

        $event = app(WebhookIngestionService::class)->ingest(
            company: $company,
            provider: ChannelProvider::WhatsAppCloud,
            payload: $payload,
            connection: $connection,
            signature: $signature,
            idempotencyKey: 'whatsapp_message_wamid.ABC123',
            eventType: 'message',
        );

        $this->assertTrue($event->signature_valid);

        $result = app(ChannelManager::class)->processEvent($event->fresh());

        $this->assertTrue($result->handled);
        $this->assertNotNull($result->lead);
        $this->assertNotNull($result->conversation);
        $this->assertNotNull($result->message);
        $this->assertSame('whatsapp', $result->lead->source);
        $this->assertSame('Sam Rivera', $result->lead->name);
        $this->assertSame('+15559876543', $result->lead->phone);
        $this->assertSame('Hi, I want a demo', $result->message->body);
        $this->assertSame($admin->id, $result->lead->created_by);
        $this->assertSame(WebhookEventStatus::Processed, $event->fresh()->status);
        $this->assertDatabaseHas('conversations', [
            'lead_id' => $result->lead->id,
            'provider' => ChannelProvider::WhatsAppCloud->value,
            'external_thread_id' => '15559876543',
        ]);
        $this->assertDatabaseHas('conversation_messages', [
            'provider_message_id' => 'wamid.ABC123',
            'body' => 'Hi, I want a demo',
        ]);
    }

    public function test_whatsapp_status_webhook_is_ignored_without_creating_lead(): void
    {
        $company = Company::factory()->create();
        User::factory()->admin()->create(['company_id' => $company->id]);
        app(CurrentCompany::class)->set($company);

        $connection = ChannelConnection::factory()->create([
            'company_id' => $company->id,
            'provider' => ChannelProvider::WhatsAppCloud,
            'external_page_id' => 'phone_123',
            'access_token' => 'wa-access-token',
        ]);

        $payload = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => 'waba_1',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'metadata' => ['phone_number_id' => 'phone_123'],
                        'statuses' => [[
                            'id' => 'wamid.STATUS1',
                            'status' => 'delivered',
                            'timestamp' => '1710000001',
                            'recipient_id' => '15559876543',
                        ]],
                    ],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);

        $signature = 'sha256='.hash_hmac('sha256', $payload, 'meta-app-secret');

        $event = app(WebhookIngestionService::class)->ingest(
            company: $company,
            provider: ChannelProvider::WhatsAppCloud,
            payload: $payload,
            connection: $connection,
            signature: $signature,
            dispatch: false,
        );

        $result = app(ChannelManager::class)->processEvent($event);

        $this->assertTrue($result->ignored);
        $this->assertSame(0, Lead::query()->count());
        $this->assertSame(0, Conversation::query()->count());
        $this->assertSame(WebhookEventStatus::Ignored, $event->fresh()->status);
    }

    public function test_admin_can_connect_and_test_whatsapp_channel(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => 'phone_123',
                'display_phone_number' => '+1 555-000-1111',
                'verified_name' => 'Acme Support',
            ]),
        ]);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('channels.store'), [
            'provider' => ChannelProvider::WhatsAppCloud->value,
            'name' => 'Main WhatsApp',
            'external_page_id' => 'phone_123',
            'external_account_id' => 'waba_99',
            'access_token' => 'wa-access-token',
        ]);

        $connection = ChannelConnection::query()->first();

        $this->assertNotNull($connection);
        $this->assertSame(ChannelProvider::WhatsAppCloud, $connection->provider);
        $response->assertRedirect(route('channels.show', $connection));

        $this->actingAs($admin)
            ->get(route('channels.show', $connection))
            ->assertOk()
            ->assertSee('Phone Number ID', false)
            ->assertSee('phone_123', false)
            ->assertSee('Verify token', false);

        $this->actingAs($admin)
            ->post(route('channels.test', $connection))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(ChannelConnectionStatus::Connected, $connection->fresh()->status);
    }

    public function test_duplicate_whatsapp_message_id_is_not_duplicated(): void
    {
        $company = Company::factory()->create();
        User::factory()->admin()->create(['company_id' => $company->id]);
        app(CurrentCompany::class)->set($company);

        $connection = ChannelConnection::factory()->create([
            'company_id' => $company->id,
            'provider' => ChannelProvider::WhatsAppCloud,
            'external_page_id' => 'phone_123',
            'access_token' => 'wa-access-token',
        ]);

        $payload = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => 'waba_1',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'metadata' => ['phone_number_id' => 'phone_123'],
                        'contacts' => [['profile' => ['name' => 'Dup'], 'wa_id' => '15551112222']],
                        'messages' => [[
                            'from' => '15551112222',
                            'id' => 'wamid.DUP1',
                            'type' => 'text',
                            'text' => ['body' => 'First'],
                        ]],
                    ],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);

        $signature = 'sha256='.hash_hmac('sha256', $payload, 'meta-app-secret');

        $first = app(WebhookIngestionService::class)->ingest(
            company: $company,
            provider: ChannelProvider::WhatsAppCloud,
            payload: $payload,
            connection: $connection,
            signature: $signature,
            idempotencyKey: 'whatsapp_message_wamid.DUP1',
            dispatch: false,
        );

        app(ChannelManager::class)->processEvent($first);

        $second = app(WebhookIngestionService::class)->ingest(
            company: $company,
            provider: ChannelProvider::WhatsAppCloud,
            payload: $payload,
            connection: $connection,
            signature: $signature,
            idempotencyKey: 'whatsapp_message_wamid.DUP1',
            dispatch: false,
        );

        $this->assertSame($first->id, $second->id);
        $this->assertSame(WebhookEventStatus::Duplicate, $second->fresh()->status);
        $this->assertSame(1, ConversationMessage::query()->where('provider_message_id', 'wamid.DUP1')->count());
        $this->assertSame(1, Lead::query()->where('phone', '+15551112222')->count());
    }
}
