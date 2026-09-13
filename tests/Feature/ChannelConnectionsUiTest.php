<?php

namespace Tests\Feature;

use App\Enums\Channels\ChannelConnectionStatus;
use App\Enums\Channels\ChannelProvider;
use App\Jobs\Channels\ProcessChannelWebhookJob;
use App\Models\ChannelConnection;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ChannelConnectionsUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_admin_can_view_channels_index_and_connect_generic_webhook(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('channels.index'))
            ->assertOk()
            ->assertSee('Channels')
            ->assertSee('Connect channel');

        $response = $this->actingAs($admin)->post(route('channels.store'), [
            'provider' => ChannelProvider::GenericWebhook->value,
            'name' => 'Website Hook',
            'external_account_id' => 'site-1',
        ]);

        $connection = ChannelConnection::query()->first();

        $this->assertNotNull($connection);
        $response->assertRedirect(route('channels.show', $connection))
            ->assertSessionHas('plain_webhook_secret');

        $this->actingAs($admin)
            ->get(route('channels.show', $connection))
            ->assertOk()
            ->assertSee('Website Hook')
            ->assertSee('Webhook endpoint')
            ->assertSee(route('webhooks.channels.inbound', $connection->uuid), false);
    }

    public function test_admin_can_test_sync_retry_and_disconnect_channel(): void
    {
        $admin = User::factory()->admin()->create();
        $connection = ChannelConnection::factory()->create([
            'company_id' => $admin->company_id,
            'provider' => ChannelProvider::GenericWebhook,
            'status' => ChannelConnectionStatus::Error,
            'error_count' => 3,
            'last_error_message' => 'Previous failure',
            'webhook_secret' => 'secret-value',
        ]);

        $this->actingAs($admin)
            ->post(route('channels.test', $connection))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(ChannelConnectionStatus::Connected, $connection->fresh()->status);
        $this->assertSame(0, $connection->fresh()->error_count);

        $this->actingAs($admin)
            ->post(route('channels.sync', $connection))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($connection->fresh()->last_sync_at);

        $connection->forceFill([
            'status' => ChannelConnectionStatus::Error,
            'error_count' => 2,
        ])->save();

        $this->actingAs($admin)
            ->post(route('channels.retry', $connection))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post(route('channels.disconnect', $connection))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(ChannelConnectionStatus::Disconnected, $connection->fresh()->status);
        $this->assertNull($connection->fresh()->access_token);
    }

    public function test_sales_can_view_but_cannot_manage_channels(): void
    {
        $sales = User::factory()->create();
        $connection = ChannelConnection::factory()->create([
            'company_id' => $sales->company_id,
        ]);

        $this->actingAs($sales)
            ->get(route('channels.index'))
            ->assertOk();

        $this->actingAs($sales)
            ->get(route('channels.create'))
            ->assertForbidden();

        $this->actingAs($sales)
            ->post(route('channels.test', $connection))
            ->assertForbidden();
    }

    public function test_channel_webhook_accepts_signed_payload(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        $connection = ChannelConnection::factory()->create([
            'company_id' => $admin->company_id,
            'provider' => ChannelProvider::GenericWebhook,
            'webhook_secret' => 'hook-secret',
            'status' => ChannelConnectionStatus::Connected,
        ]);

        $payload = json_encode([
            'type' => 'lead',
            'name' => 'Webhook User',
            'email' => 'webhook-user@example.com',
        ], JSON_THROW_ON_ERROR);

        $signature = 'sha256='.hash_hmac('sha256', $payload, 'hook-secret');

        $this->call(
            'POST',
            route('webhooks.channels.inbound', $connection->uuid),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CHANNEL_SIGNATURE' => $signature,
            ],
            $payload,
        )->assertStatus(202)
            ->assertJsonPath('message', 'accepted');

        Queue::assertPushed(ProcessChannelWebhookJob::class);
    }

    public function test_tenant_cannot_view_another_company_channel(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $adminA = User::factory()->admin()->create(['company_id' => $companyA->id]);
        User::factory()->admin()->create(['company_id' => $companyB->id]);

        $connection = ChannelConnection::factory()->create([
            'company_id' => $companyB->id,
        ]);

        $this->actingAs($adminA)
            ->get(route('channels.show', $connection))
            ->assertNotFound();
    }
}
