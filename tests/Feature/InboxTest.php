<?php

namespace Tests\Feature;

use App\Enums\Channels\ChannelProvider;
use App\Models\ChannelConnection;
use App\Models\ChannelContact;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Lead;
use App\Models\User;
use App\Support\CurrentCompany;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        config(['channels.meta.graph_version' => 'v21.0']);
    }

    public function test_sales_can_view_inbox_and_conversation(): void
    {
        $sales = User::factory()->create();
        $sales->syncRolesFromLegacyColumn();

        [$conversation] = $this->makeWhatsAppConversation($sales->company_id, unread: 2);

        $this->actingAs($sales)
            ->get(route('inbox.index'))
            ->assertOk()
            ->assertSee('Inbox', false)
            ->assertSee($conversation->contact->display_name, false);

        $this->actingAs($sales)
            ->get(route('inbox.show', $conversation))
            ->assertOk()
            ->assertSee('Hi from customer', false);

        $this->assertSame(0, $conversation->fresh()->unread_count);
    }

    public function test_agent_can_send_whatsapp_reply(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.OUTBOUND1']],
            ]),
        ]);

        $admin = User::factory()->admin()->create();
        [$conversation] = $this->makeWhatsAppConversation($admin->company_id);

        $this->actingAs($admin)
            ->post(route('inbox.reply', $conversation), [
                'body' => 'Thanks for reaching out!',
            ])
            ->assertRedirect(route('inbox.show', $conversation))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('conversation_messages', [
            'conversation_id' => $conversation->id,
            'direction' => ConversationMessage::DIRECTION_OUTBOUND,
            'body' => 'Thanks for reaching out!',
            'provider_message_id' => 'wamid.OUTBOUND1',
            'status' => 'sent',
            'user_id' => $admin->id,
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'graph.facebook.com/v21.0/phone_123/messages')
                && $request['to'] === '15559876543'
                && $request['text']['body'] === 'Thanks for reaching out!';
        });
    }

    public function test_admin_can_assign_conversation(): void
    {
        $admin = User::factory()->admin()->create();
        $sales = User::factory()->create(['company_id' => $admin->company_id]);
        $sales->syncRolesFromLegacyColumn();

        [$conversation] = $this->makeWhatsAppConversation($admin->company_id);

        $this->actingAs($admin)
            ->post(route('inbox.assign', $conversation), [
                'assigned_to' => $sales->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($sales->id, $conversation->fresh()->assigned_to);
    }

    public function test_sales_cannot_assign_conversation(): void
    {
        $sales = User::factory()->create();
        $sales->syncRolesFromLegacyColumn();

        [$conversation] = $this->makeWhatsAppConversation($sales->company_id);

        $this->actingAs($sales)
            ->post(route('inbox.assign', $conversation), [
                'assigned_to' => $sales->id,
            ])
            ->assertForbidden();
    }

    public function test_closed_conversation_cannot_be_replied(): void
    {
        $admin = User::factory()->admin()->create();
        [$conversation] = $this->makeWhatsAppConversation($admin->company_id);
        $conversation->forceFill(['status' => Conversation::STATUS_CLOSED])->save();

        $this->actingAs($admin)
            ->post(route('inbox.reply', $conversation), [
                'body' => 'Should fail',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('conversation_messages', [
            'conversation_id' => $conversation->id,
            'body' => 'Should fail',
        ]);
    }

    public function test_tenant_cannot_view_another_company_conversation(): void
    {
        $companyA = \App\Models\Company::factory()->create();
        $companyB = \App\Models\Company::factory()->create();
        $adminA = User::factory()->admin()->create(['company_id' => $companyA->id]);
        $adminB = User::factory()->admin()->create(['company_id' => $companyB->id]);

        [$conversation] = $this->makeWhatsAppConversation($adminA->company_id);

        $this->actingAs($adminB)
            ->get(route('inbox.show', $conversation))
            ->assertNotFound();
    }

    /**
     * @return array{0: Conversation, 1: ChannelConnection, 2: ChannelContact}
     */
    protected function makeWhatsAppConversation(int $companyId, int $unread = 0): array
    {
        app(CurrentCompany::class)->set($companyId);

        $connection = ChannelConnection::factory()->create([
            'company_id' => $companyId,
            'provider' => ChannelProvider::WhatsAppCloud,
            'external_page_id' => 'phone_123',
            'access_token' => 'wa-token',
            'name' => 'Main WhatsApp',
        ]);

        $lead = Lead::factory()->create([
            'company_id' => $companyId,
            'name' => 'Sam Rivera',
            'phone' => '+15559876543',
        ]);

        $contact = ChannelContact::factory()->create([
            'company_id' => $companyId,
            'channel_connection_id' => $connection->id,
            'lead_id' => $lead->id,
            'provider' => ChannelProvider::WhatsAppCloud,
            'external_user_id' => '15559876543',
            'phone' => '+15559876543',
            'display_name' => 'Sam Rivera',
        ]);

        $conversation = Conversation::factory()->create([
            'company_id' => $companyId,
            'channel_connection_id' => $connection->id,
            'channel_contact_id' => $contact->id,
            'lead_id' => $lead->id,
            'provider' => ChannelProvider::WhatsAppCloud,
            'external_thread_id' => '15559876543',
            'status' => Conversation::STATUS_OPEN,
            'unread_count' => $unread,
            'last_message_at' => now(),
            'last_inbound_at' => now(),
        ]);

        ConversationMessage::factory()->create([
            'company_id' => $companyId,
            'conversation_id' => $conversation->id,
            'channel_contact_id' => $contact->id,
            'direction' => ConversationMessage::DIRECTION_INBOUND,
            'body' => 'Hi from customer',
            'provider_message_id' => 'wamid.IN1',
            'status' => 'received',
        ]);

        return [$conversation, $connection, $contact];
    }
}
