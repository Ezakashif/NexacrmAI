<?php

namespace App\Services\Channels;

use App\Enums\Channels\ChannelConnectionStatus;
use App\Enums\Channels\ChannelProvider;
use App\Models\ChannelConnection;
use App\Models\Company;
use App\Services\ActivityLogger;
use App\Services\Channels\Meta\MetaGraphClient;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChannelConnectionService
{
    public function __construct(
        protected ChannelManager $channels,
        protected MetaGraphClient $graph,
    ) {}

    /**
     * @param  array{
     *     provider: string|ChannelProvider,
     *     name: string,
     *     external_account_id?: string|null,
     *     external_page_id?: string|null,
     *     access_token?: string|null,
     *     webhook_secret?: string|null,
     *     verify_token?: string|null,
     *     token_expires_at?: string|null
     * }  $data
     */
    /**
     * @return array{connection: ChannelConnection, plain_webhook_secret: string}
     */
    public function create(Company $company, array $data): array
    {
        $provider = $data['provider'] instanceof ChannelProvider
            ? $data['provider']
            : ChannelProvider::from((string) $data['provider']);

        if (! ($this->enabledProviders()[$provider->value] ?? false)) {
            throw ValidationException::withMessages([
                'provider' => 'This channel provider is not enabled.',
            ]);
        }

        $secret = filled($data['webhook_secret'] ?? null)
            ? (string) $data['webhook_secret']
            : Str::random(40);

        $connection = new ChannelConnection([
            'provider' => $provider,
            'name' => $data['name'],
            'status' => ChannelConnectionStatus::Connected,
            'external_account_id' => $data['external_account_id'] ?? null,
            'external_page_id' => $data['external_page_id'] ?? null,
            'access_token' => $data['access_token'] ?? null,
            'webhook_secret' => $secret,
            'verify_token' => $data['verify_token'] ?? Str::random(32),
            'token_expires_at' => $data['token_expires_at'] ?? null,
            'meta' => [
                'created_via' => 'channels_ui',
            ],
        ]);
        $connection->company_id = $company->id;
        $connection->save();

        ActivityLogger::log('channel.connection_created', $connection, [
            'provider' => $provider->value,
            'name' => $connection->name,
        ]);

        return [
            'connection' => $connection,
            'plain_webhook_secret' => $secret,
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function testConnection(ChannelConnection $connection): array
    {
        if ($connection->status === ChannelConnectionStatus::Disconnected) {
            return ['ok' => false, 'message' => 'Reconnect this channel before testing.'];
        }

        $provider = $connection->provider;

        if (! $this->channels->has($provider)) {
            if (filled($connection->access_token) || filled($connection->webhook_secret)) {
                $connection->markHealthy();

                ActivityLogger::log('channel.connection_tested', $connection, [
                    'provider' => $provider->value,
                    'result' => 'credentials_present',
                ]);

                return [
                    'ok' => true,
                    'message' => $provider->label().' credentials look present. Full provider adapter arrives in a later milestone.',
                ];
            }

            $connection->markError('Missing credentials for '.$provider->label());

            return ['ok' => false, 'message' => 'Add credentials before testing this channel.'];
        }

        if ($provider === ChannelProvider::FacebookLeadAds) {
            return $this->testFacebookLeadAdsConnection($connection);
        }

        if ($provider === ChannelProvider::WhatsAppCloud) {
            return $this->testWhatsAppCloudConnection($connection);
        }

        if (! filled($connection->webhook_secret)) {
            $connection->markError('Webhook secret is missing.');

            return ['ok' => false, 'message' => 'Webhook secret is missing. Regenerate the secret and try again.'];
        }

        $payload = json_encode([
            'type' => 'lead',
            'name' => 'Channel Connection Test',
            'email' => 'channel-test@example.invalid',
        ], JSON_THROW_ON_ERROR);

        $signature = 'sha256='.hash_hmac('sha256', $payload, (string) $connection->webhook_secret);
        $valid = $this->channels->adapter($provider)
            ->validateSignature($payload, $signature, $connection);

        if (! $valid) {
            $connection->markError('Signature validation failed during connection test.');

            ActivityLogger::log('channel.connection_tested', $connection, [
                'provider' => $provider->value,
                'result' => 'failed',
            ]);

            return ['ok' => false, 'message' => 'Connection test failed signature validation.'];
        }

        $connection->markHealthy();

        ActivityLogger::log('channel.connection_tested', $connection, [
            'provider' => $provider->value,
            'result' => 'passed',
        ]);

        return ['ok' => true, 'message' => 'Connection test passed. Webhook signature validation is healthy.'];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    protected function testFacebookLeadAdsConnection(ChannelConnection $connection): array
    {
        if (! filled($connection->external_page_id)) {
            $connection->markError('Facebook Page ID is missing.');

            return ['ok' => false, 'message' => 'Add the Facebook Page ID before testing this channel.'];
        }

        if (! filled($connection->access_token)) {
            $connection->markError('Facebook page access token is missing.');

            return ['ok' => false, 'message' => 'Add the Facebook page access token before testing this channel.'];
        }

        if (! filled(config('channels.meta.app_secret')) && ! filled($connection->webhook_secret)) {
            $connection->markError('Meta app secret is not configured.');

            return ['ok' => false, 'message' => 'Set META_APP_SECRET in the environment or store a webhook secret on this connection.'];
        }

        try {
            $page = $this->graph->fetchPage((string) $connection->external_page_id, (string) $connection->access_token);
        } catch (\Throwable $e) {
            $connection->markError($e->getMessage());

            ActivityLogger::log('channel.connection_tested', $connection, [
                'provider' => $connection->provider->value,
                'result' => 'failed',
            ]);

            return ['ok' => false, 'message' => 'Facebook connection test failed: '.$e->getMessage()];
        }

        $connection->markHealthy();

        ActivityLogger::log('channel.connection_tested', $connection, [
            'provider' => $connection->provider->value,
            'result' => 'passed',
            'page_name' => $page['name'] ?? null,
        ]);

        $pageName = filled($page['name'] ?? null) ? ' ('.$page['name'].')' : '';

        return ['ok' => true, 'message' => 'Facebook page token is valid'.$pageName.'. Webhook verification uses the verify token shown on this page.'];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    protected function testWhatsAppCloudConnection(ChannelConnection $connection): array
    {
        if (! filled($connection->external_page_id)) {
            $connection->markError('WhatsApp Phone Number ID is missing.');

            return ['ok' => false, 'message' => 'Add the WhatsApp Phone Number ID before testing this channel.'];
        }

        if (! filled($connection->access_token)) {
            $connection->markError('WhatsApp Cloud access token is missing.');

            return ['ok' => false, 'message' => 'Add the WhatsApp Cloud access token before testing this channel.'];
        }

        if (! filled(config('channels.meta.app_secret')) && ! filled($connection->webhook_secret)) {
            $connection->markError('Meta app secret is not configured.');

            return ['ok' => false, 'message' => 'Set META_APP_SECRET in the environment or store a webhook secret on this connection.'];
        }

        try {
            $phone = $this->graph->fetchPhoneNumber(
                (string) $connection->external_page_id,
                (string) $connection->access_token,
            );
        } catch (\Throwable $e) {
            $connection->markError($e->getMessage());

            ActivityLogger::log('channel.connection_tested', $connection, [
                'provider' => $connection->provider->value,
                'result' => 'failed',
            ]);

            return ['ok' => false, 'message' => 'WhatsApp connection test failed: '.$e->getMessage()];
        }

        $connection->markHealthy();

        ActivityLogger::log('channel.connection_tested', $connection, [
            'provider' => $connection->provider->value,
            'result' => 'passed',
            'display_phone_number' => $phone['display_phone_number'] ?? null,
            'verified_name' => $phone['verified_name'] ?? null,
        ]);

        $label = filled($phone['display_phone_number'] ?? null)
            ? ' ('.$phone['display_phone_number'].')'
            : (filled($phone['verified_name'] ?? null) ? ' ('.$phone['verified_name'].')' : '');

        return ['ok' => true, 'message' => 'WhatsApp Cloud token is valid'.$label.'. Use the verify token on this page for Meta webhook setup.'];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function syncNow(ChannelConnection $connection): array
    {
        if ($connection->status === ChannelConnectionStatus::Disconnected) {
            return ['ok' => false, 'message' => 'Reconnect this channel before syncing.'];
        }

        // Provider-specific sync adapters land in later milestones.
        $connection->forceFill([
            'last_sync_at' => now(),
            'status' => $connection->status === ChannelConnectionStatus::Error
                ? ChannelConnectionStatus::Connected
                : $connection->status,
        ])->save();

        if ($connection->token_expires_at && $connection->token_expires_at->isPast()) {
            $connection->forceFill([
                'status' => ChannelConnectionStatus::TokenExpiring,
            ])->save();
        }

        ActivityLogger::log('channel.connection_synced', $connection, [
            'provider' => $connection->provider->value,
        ]);

        return ['ok' => true, 'message' => 'Sync completed. Last sync time updated.'];
    }

    public function retry(ChannelConnection $connection): ChannelConnection
    {
        $connection->forceFill([
            'status' => ChannelConnectionStatus::Pending,
            'error_count' => 0,
            'last_error_at' => null,
            'last_error_message' => null,
        ])->save();

        ActivityLogger::log('channel.connection_retry', $connection, [
            'provider' => $connection->provider->value,
        ]);

        $result = $this->testConnection($connection->fresh());

        return $connection->fresh();
    }

    public function disconnect(ChannelConnection $connection): ChannelConnection
    {
        $connection->forceFill([
            'status' => ChannelConnectionStatus::Disconnected,
            'access_token' => null,
            'refresh_token' => null,
            'last_error_message' => null,
            'error_count' => 0,
            'last_error_at' => null,
        ])->save();

        ActivityLogger::log('channel.connection_disconnected', $connection, [
            'provider' => $connection->provider->value,
        ]);

        return $connection;
    }

    public function regenerateWebhookSecret(ChannelConnection $connection): string
    {
        $secret = Str::random(40);

        $connection->forceFill([
            'webhook_secret' => $secret,
            'status' => $connection->status === ChannelConnectionStatus::Disconnected
                ? ChannelConnectionStatus::Disconnected
                : ChannelConnectionStatus::Connected,
        ])->save();

        ActivityLogger::log('channel.connection_secret_rotated', $connection, [
            'provider' => $connection->provider->value,
        ]);

        return $secret;
    }

    /**
     * @return array<string, string> value => label
     */
    public function enabledProviderOptions(): array
    {
        $options = [];

        foreach ($this->enabledProviders() as $value => $enabled) {
            if (! $enabled) {
                continue;
            }

            $options[$value] = ChannelProvider::from($value)->label();
        }

        return $options;
    }

    /**
     * @return array<string, bool>
     */
    protected function enabledProviders(): array
    {
        $providers = config('channels.providers', []);
        $enabled = [];

        foreach ($providers as $key => $meta) {
            $enabled[$key] = (bool) ($meta['enabled'] ?? false);
        }

        return $enabled;
    }

    public function healthLabel(ChannelConnection $connection): string
    {
        return match ($connection->status) {
            ChannelConnectionStatus::Connected => $this->isTokenExpiringSoon($connection) ? 'Warning' : 'Healthy',
            ChannelConnectionStatus::TokenExpiring => 'Warning',
            ChannelConnectionStatus::Error => 'Error',
            ChannelConnectionStatus::Pending => 'Pending',
            ChannelConnectionStatus::Disconnected => 'Disconnected',
        };
    }

    public function isTokenExpiringSoon(ChannelConnection $connection, int $withinDays = 7): bool
    {
        if (! $connection->token_expires_at) {
            return false;
        }

        return $connection->token_expires_at->lessThanOrEqualTo(now()->addDays($withinDays));
    }
}
