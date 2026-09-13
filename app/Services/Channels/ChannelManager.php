<?php

namespace App\Services\Channels;

use App\Enums\Channels\ChannelProvider;
use App\Models\ChannelWebhookEvent;
use App\Services\ActivityLogger;
use App\Services\Channels\Contracts\ChannelAdapter;
use App\Services\Channels\DTOs\ChannelProcessResult;
use App\Support\CurrentCompany;
use InvalidArgumentException;
use Throwable;

class ChannelManager
{
    /** @var array<string, ChannelAdapter> */
    protected array $adapters = [];

    /**
     * @param  iterable<ChannelAdapter>  $adapters
     */
    public function __construct(iterable $adapters = [])
    {
        foreach ($adapters as $adapter) {
            $this->register($adapter);
        }
    }

    public function register(ChannelAdapter $adapter): void
    {
        $this->adapters[$adapter->provider()->value] = $adapter;
    }

    public function has(ChannelProvider $provider): bool
    {
        return isset($this->adapters[$provider->value]);
    }

    public function adapter(ChannelProvider $provider): ChannelAdapter
    {
        if (! $this->has($provider)) {
            throw new InvalidArgumentException("No channel adapter registered for [{$provider->value}].");
        }

        return $this->adapters[$provider->value];
    }

    /**
     * @return list<ChannelProvider>
     */
    public function providers(): array
    {
        return array_map(
            fn (string $value) => ChannelProvider::from($value),
            array_keys($this->adapters),
        );
    }

    public function processEvent(ChannelWebhookEvent $event): ChannelProcessResult
    {
        $currentCompany = app(CurrentCompany::class);
        $previous = $currentCompany->id();
        $currentCompany->set((int) $event->company_id);

        try {
            $event->markProcessing();

            if ($event->signature_valid === false) {
                $event->markFailed('Invalid webhook signature.');

                return ChannelProcessResult::ignored('Invalid webhook signature.');
            }

            $adapter = $this->adapter($event->provider);
            $result = $adapter->process($event);

            if ($result->ignored) {
                $event->markIgnored($result->reason);
            } else {
                $event->markProcessed();
                $event->connection?->markHealthy();
            }

            ActivityLogger::log('channel.webhook_processed', $event->connection ?? $event, [
                'provider' => $event->provider->value,
                'webhook_event_id' => $event->id,
                'handled' => $result->handled,
                'ignored' => $result->ignored,
                'lead_id' => $result->lead?->id,
                'conversation_id' => $result->conversation?->id,
            ]);

            return $result;
        } catch (Throwable $e) {
            $event->markFailed($e->getMessage());
            $event->connection?->markError($e->getMessage());

            ActivityLogger::log('channel.webhook_failed', $event->connection ?? $event, [
                'provider' => $event->provider->value,
                'webhook_event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            if ($previous !== null) {
                $currentCompany->set($previous);
            } else {
                $currentCompany->clear();
            }
        }
    }
}
