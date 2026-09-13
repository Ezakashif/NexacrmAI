<?php

namespace App\Providers;

use App\Models\ChannelConnection;
use App\Services\Channels\Adapters\FacebookLeadAdsAdapter;
use App\Services\Channels\Adapters\GenericWebhookAdapter;
use App\Services\Channels\Adapters\WhatsAppCloudAdapter;
use App\Services\Channels\ChannelManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ChannelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ChannelManager::class, function ($app) {
            $manager = new ChannelManager;

            // M1: generic webhook adapter powers the engine end-to-end.
            $manager->register($app->make(GenericWebhookAdapter::class));
            // M3: Facebook Lead Ads adapter.
            $manager->register($app->make(FacebookLeadAdsAdapter::class));
            // WhatsApp Cloud API adapter.
            $manager->register($app->make(WhatsAppCloudAdapter::class));

            return $manager;
        });
    }

    public function boot(): void
    {
        // Resource route param {channel} resolves to ChannelConnection.
        Route::bind('channel', function (string $value) {
            return ChannelConnection::query()
                ->where(function ($query) use ($value) {
                    $query->where('id', $value)->orWhere('uuid', $value);
                })
                ->firstOrFail();
        });
    }
}
