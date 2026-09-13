<?php

namespace App\Enums\Channels;

enum WebhookEventStatus: string
{
    case Received = 'received';
    case Queued = 'queued';
    case Processing = 'processing';
    case Processed = 'processed';
    case Failed = 'failed';
    case Ignored = 'ignored';
    case Duplicate = 'duplicate';
}
