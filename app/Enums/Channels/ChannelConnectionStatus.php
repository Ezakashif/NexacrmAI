<?php

namespace App\Enums\Channels;

enum ChannelConnectionStatus: string
{
    case Connected = 'connected';
    case Disconnected = 'disconnected';
    case Error = 'error';
    case Pending = 'pending';
    case TokenExpiring = 'token_expiring';

    public function label(): string
    {
        return match ($this) {
            self::Connected => 'Connected',
            self::Disconnected => 'Disconnected',
            self::Error => 'Error',
            self::Pending => 'Pending',
            self::TokenExpiring => 'Token expiring',
        };
    }
}
