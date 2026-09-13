<?php

namespace App\Enums\Channels;

enum ChannelProvider: string
{
    case FacebookLeadAds = 'facebook_lead_ads';
    case FacebookMessenger = 'facebook_messenger';
    case InstagramLeadForms = 'instagram_lead_forms';
    case InstagramDm = 'instagram_dm';
    case WhatsAppCloud = 'whatsapp_cloud';
    case WebsiteForm = 'website_form';
    case GenericWebhook = 'generic_webhook';
    case PublicApi = 'public_api';

    public function label(): string
    {
        return (string) config("channels.providers.{$this->value}.label", $this->name);
    }

    public function isMessaging(): bool
    {
        return in_array($this, [
            self::FacebookMessenger,
            self::InstagramDm,
            self::WhatsAppCloud,
        ], true);
    }

    public function isLeadAds(): bool
    {
        return in_array($this, [
            self::FacebookLeadAds,
            self::InstagramLeadForms,
        ], true);
    }
}
