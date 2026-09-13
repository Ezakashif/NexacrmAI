<?php

namespace Tests\Feature;

use App\Enums\Channels\ChannelConnectionStatus;
use App\Enums\Channels\ChannelProvider;
use App\Enums\Channels\WebhookEventStatus;
use App\Jobs\Channels\ProcessChannelWebhookJob;
use App\Models\ChannelConnection;
use App\Models\Company;
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

class FacebookLeadAdsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        config(['channels.meta.app_secret' => 'meta-app-secret']);
        config(['channels.meta.graph_version' => 'v21.0']);
    }

    public function test_meta_webhook_verification_returns_challenge(): void
    {
        $admin = User::factory()->admin()->create();
        $connection = ChannelConnection::factory()->create([
            'company_id' => $admin->company_id,
            'provider' => ChannelProvider::FacebookLeadAds,
            'verify_token' => 'verify-token-123',
            'external_page_id' => 'page_123',
            'access_token' => 'page-token',
        ]);

        $this->get(route('webhooks.channels.inbound', $connection->uuid).'?'.http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'verify-token-123',
            'hub.challenge' => 'challenge-abc',
        ]))
            ->assertOk()
            ->assertSee('challenge-abc');
    }

    public function test_meta_webhook_verification_rejects_invalid_token(): void
    {
        $admin = User::factory()->admin()->create();
        $connection = ChannelConnection::factory()->create([
            'company_id' => $admin->company_id,
            'provider' => ChannelProvider::FacebookLeadAds,
            'verify_token' => 'verify-token-123',
        ]);

        $this->get(route('webhooks.channels.inbound', $connection->uuid).'?'.http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'wrong-token',
            'hub.challenge' => 'challenge-abc',
        ]))
            ->assertForbidden();
    }

    public function test_facebook_leadgen_webhook_is_processed_into_crm_lead(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);
        app(CurrentCompany::class)->set($company);

        $connection = ChannelConnection::factory()->create([
            'company_id' => $company->id,
            'provider' => ChannelProvider::FacebookLeadAds,
            'external_page_id' => 'page_123',
            'access_token' => 'page-access-token',
            'webhook_secret' => 'ignored-for-meta',
            'status' => ChannelConnectionStatus::Connected,
        ]);

        $payload = json_encode([
            'object' => 'page',
            'entry' => [[
                'id' => 'page_123',
                'time' => 1710000000,
                'changes' => [[
                    'field' => 'leadgen',
                    'value' => [
                        'leadgen_id' => 'leadgen_999',
                        'page_id' => 'page_123',
                        'form_id' => 'form_55',
                        'adgroup_id' => 'adset_77',
                        'ad_id' => 'ad_88',
                        'created_time' => 1710000000,
                    ],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => 'leadgen_999',
                'created_time' => '2024-03-09T12:00:00+0000',
                'field_data' => [
                    ['name' => 'full_name', 'values' => ['Taylor Brooks']],
                    ['name' => 'email', 'values' => ['taylor@example.com']],
                    ['name' => 'phone_number', 'values' => ['+15550111222']],
                ],
                'ad_id' => 'ad_88',
                'ad_name' => 'Spring Promo',
                'adset_id' => 'adset_77',
                'campaign_id' => 'camp_44',
                'form_id' => 'form_55',
            ]),
        ]);

        $signature = 'sha256='.hash_hmac('sha256', $payload, 'meta-app-secret');

        $event = app(WebhookIngestionService::class)->ingest(
            company: $company,
            provider: ChannelProvider::FacebookLeadAds,
            payload: $payload,
            connection: $connection,
            signature: $signature,
            idempotencyKey: 'facebook_leadgen_leadgen_999',
            eventType: 'leadgen',
        );

        $this->assertTrue($event->signature_valid);
        $this->assertSame(WebhookEventStatus::Queued, $event->status);

        app(ChannelManager::class)->processEvent($event->fresh());

        $lead = Lead::query()->where('email', 'taylor@example.com')->first();

        $this->assertNotNull($lead);
        $this->assertSame('facebook', $lead->source);
        $this->assertSame($admin->id, $lead->created_by);
        $this->assertSame(WebhookEventStatus::Processed, $event->fresh()->status);
        $this->assertDatabaseHas('lead_channel_meta', [
            'lead_id' => $lead->id,
            'campaign_id' => 'camp_44',
            'form_id' => 'form_55',
            'ad_id' => 'ad_88',
            'page_id' => 'page_123',
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'graph.facebook.com/v21.0/leadgen_999')
                && $request['access_token'] === 'page-access-token';
        });
    }

    public function test_invalid_meta_signature_rejects_webhook(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        User::factory()->admin()->create(['company_id' => $company->id]);

        $connection = ChannelConnection::factory()->create([
            'company_id' => $company->id,
            'provider' => ChannelProvider::FacebookLeadAds,
            'external_page_id' => 'page_123',
            'access_token' => 'page-access-token',
        ]);

        $payload = json_encode([
            'object' => 'page',
            'entry' => [[
                'id' => 'page_123',
                'changes' => [[
                    'field' => 'leadgen',
                    'value' => ['leadgen_id' => 'leadgen_bad'],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);

        $event = app(WebhookIngestionService::class)->ingest(
            company: $company,
            provider: ChannelProvider::FacebookLeadAds,
            payload: $payload,
            connection: $connection,
            signature: 'sha256=invalid',
        );

        $this->assertFalse($event->signature_valid);
        $this->assertSame(WebhookEventStatus::Failed, $event->status);
        Queue::assertNotPushed(ProcessChannelWebhookJob::class);
    }

    public function test_admin_can_connect_facebook_lead_ads_channel(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => 'page_123',
                'name' => 'Acme Page',
            ]),
        ]);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('channels.store'), [
            'provider' => ChannelProvider::FacebookLeadAds->value,
            'name' => 'Main Facebook Page',
            'external_page_id' => 'page_123',
            'access_token' => 'page-access-token',
        ]);

        $connection = ChannelConnection::query()->first();

        $this->assertNotNull($connection);
        $this->assertSame(ChannelProvider::FacebookLeadAds, $connection->provider);
        $response->assertRedirect(route('channels.show', $connection));

        $this->actingAs($admin)
            ->post(route('channels.test', $connection))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(ChannelConnectionStatus::Connected, $connection->fresh()->status);
    }

    public function test_facebook_leadgen_idempotency_prevents_duplicate_events(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        User::factory()->admin()->create(['company_id' => $company->id]);

        $connection = ChannelConnection::factory()->create([
            'company_id' => $company->id,
            'provider' => ChannelProvider::FacebookLeadAds,
            'external_page_id' => 'page_123',
            'access_token' => 'page-access-token',
        ]);

        $payload = json_encode([
            'object' => 'page',
            'entry' => [[
                'id' => 'page_123',
                'changes' => [[
                    'field' => 'leadgen',
                    'value' => ['leadgen_id' => 'leadgen_dup'],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);

        $signature = 'sha256='.hash_hmac('sha256', $payload, 'meta-app-secret');

        $first = app(WebhookIngestionService::class)->ingest(
            company: $company,
            provider: ChannelProvider::FacebookLeadAds,
            payload: $payload,
            connection: $connection,
            signature: $signature,
            idempotencyKey: 'facebook_leadgen_leadgen_dup',
        );

        $second = app(WebhookIngestionService::class)->ingest(
            company: $company,
            provider: ChannelProvider::FacebookLeadAds,
            payload: $payload,
            connection: $connection,
            signature: $signature,
            idempotencyKey: 'facebook_leadgen_leadgen_dup',
        );

        $this->assertSame($first->id, $second->id);
        $this->assertSame(WebhookEventStatus::Duplicate, $second->fresh()->status);
    }
}
