<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @return list<string>
     */
    private function tables(): array
    {
        return [
            'channel_connections',
            'channel_webhook_events',
            'channel_contacts',
            'conversations',
            'conversation_messages',
            'lead_channel_meta',
        ];
    }

    public function up(): void
    {
        // Recover from a previous MySQL failure that created only some tables
        // (e.g. index name too long) before the migration row was recorded.
        $existing = collect($this->tables())->filter(fn (string $table) => Schema::hasTable($table));

        if ($existing->count() === count($this->tables())) {
            return;
        }

        if ($existing->isNotEmpty()) {
            foreach (array_reverse($this->tables()) as $table) {
                Schema::dropIfExists($table);
            }
        }

        Schema::create('channel_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('provider', 50);
            $table->string('name');
            $table->string('status', 32)->default('disconnected')->index();
            $table->string('external_account_id')->nullable()->index();
            $table->string('external_page_id')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->string('verify_token')->nullable();
            $table->timestamp('token_expires_at')->nullable()->index();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_event_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->unsignedInteger('error_count')->default(0);
            $table->string('last_error_message', 1000)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            // MySQL identifier limit is 64 chars — keep custom short names.
            $table->unique(['company_id', 'provider', 'external_account_id'], 'ch_conn_company_provider_acct_uq');
            $table->index(['company_id', 'provider', 'status'], 'ch_conn_company_provider_status_idx');
        });

        Schema::create('channel_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_connection_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('provider', 50)->index();
            $table->string('event_type', 100)->nullable();
            $table->string('idempotency_key')->nullable();
            $table->string('status', 32)->default('received')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->json('headers')->nullable();
            $table->longText('payload');
            $table->string('signature')->nullable();
            $table->boolean('signature_valid')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'provider', 'idempotency_key'], 'ch_wh_company_provider_idem_uq');
            $table->index(['channel_connection_id', 'status'], 'ch_wh_connection_status_idx');
        });

        Schema::create('channel_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_connection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 50);
            $table->string('external_user_id')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('display_name')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'provider', 'external_user_id'], 'ch_contact_company_provider_ext_uq');
            $table->index(['company_id', 'email'], 'ch_contact_company_email_idx');
            $table->index(['company_id', 'phone'], 'ch_contact_company_phone_idx');
            $table->index(['lead_id']);
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_connection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('channel_contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider', 50);
            $table->string('external_thread_id')->nullable();
            $table->string('status', 32)->default('open')->index();
            $table->string('subject')->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamp('last_inbound_at')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'provider', 'external_thread_id'], 'ch_conv_company_provider_thread_uq');
            $table->index(['company_id', 'status', 'last_message_at'], 'ch_conv_company_status_last_idx');
            $table->index(['assigned_to']);
        });

        Schema::create('conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction', 16);
            $table->string('type', 32)->default('text');
            $table->string('provider_message_id')->nullable();
            $table->text('body')->nullable();
            $table->string('status', 32)->default('received');
            $table->timestamp('sent_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'provider_message_id'], 'ch_msg_company_provider_msg_uq');
            $table->index(['conversation_id', 'sent_at'], 'ch_msg_conversation_sent_idx');
        });

        Schema::create('lead_channel_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_connection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('channel_webhook_event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 50);
            $table->string('campaign_id')->nullable();
            $table->string('campaign_name')->nullable();
            $table->string('adset_id')->nullable();
            $table->string('ad_id')->nullable();
            $table->string('ad_name')->nullable();
            $table->string('form_id')->nullable();
            $table->string('form_name')->nullable();
            $table->string('page_id')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->unique(['lead_id', 'provider'], 'lead_ch_meta_lead_provider_uq');
            $table->index(['company_id', 'provider', 'campaign_id'], 'lead_ch_meta_company_camp_idx');
        });
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables()) as $table) {
            Schema::dropIfExists($table);
        }
    }
};
