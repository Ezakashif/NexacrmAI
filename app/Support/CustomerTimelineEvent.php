<?php

namespace App\Support;

use Carbon\CarbonInterface;

class CustomerTimelineEvent
{
    public function __construct(
        public readonly string $type,
        public readonly string $label,
        public readonly CarbonInterface $occurredAt,
        public readonly ?string $summary = null,
        public readonly ?string $actorName = null,
        public readonly string $icon = 'fas fa-circle',
        public readonly string $color = 'secondary',
        public readonly bool $fromLead = false,
        public readonly ?string $url = null,
    ) {}
}
