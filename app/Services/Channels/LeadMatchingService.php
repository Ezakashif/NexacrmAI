<?php

namespace App\Services\Channels;

use App\Enums\Channels\ChannelProvider;
use App\Models\ChannelContact;
use App\Models\ChannelConnection;
use App\Models\Lead;
use App\Services\Channels\DTOs\InboundLeadDTO;
use App\Services\Channels\DTOs\InboundMessageDTO;

class LeadMatchingService
{
    public function matchLead(InboundLeadDTO|InboundMessageDTO $dto, int $companyId): ?Lead
    {
        $config = config('channels.lead_matching', []);

        if (($config['match_by_external_id'] ?? true) && filled($dto->externalUserId ?? null)) {
            $contact = ChannelContact::query()
                ->where('company_id', $companyId)
                ->where('provider', $dto->provider)
                ->where('external_user_id', $dto->externalUserId)
                ->whereNotNull('lead_id')
                ->first();

            if ($contact?->lead_id) {
                return Lead::query()->find($contact->lead_id);
            }
        }

        if (($config['match_by_email'] ?? true) && filled($dto->email ?? null)) {
            $lead = Lead::query()
                ->where('company_id', $companyId)
                ->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $dto->email)])
                ->latest('id')
                ->first();

            if ($lead) {
                return $lead;
            }
        }

        if (($config['match_by_phone'] ?? true) && filled($dto->phone ?? null)) {
            $normalized = preg_replace('/\D+/', '', (string) $dto->phone);

            if (filled($normalized)) {
                $lead = Lead::query()
                    ->where('company_id', $companyId)
                    ->whereNotNull('phone')
                    ->get()
                    ->first(function (Lead $lead) use ($normalized) {
                        return preg_replace('/\D+/', '', (string) $lead->phone) === $normalized;
                    });

                if ($lead) {
                    return $lead;
                }
            }
        }

        return null;
    }

    public function upsertContact(
        ChannelProvider $provider,
        int $companyId,
        ?ChannelConnection $connection,
        ?Lead $lead,
        ?string $externalUserId,
        ?string $email,
        ?string $phone,
        ?string $displayName = null,
    ): ChannelContact {
        $query = ChannelContact::query()->where('company_id', $companyId)->where('provider', $provider);

        if (filled($externalUserId)) {
            $contact = (clone $query)->where('external_user_id', $externalUserId)->first();
        } else {
            $contact = null;
        }

        if (! $contact && filled($email)) {
            $contact = (clone $query)
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                ->first();
        }

        if (! $contact) {
            $contact = new ChannelContact;
            $contact->company_id = $companyId;
            $contact->provider = $provider;
        }

        $contact->fill([
            'channel_connection_id' => $connection?->id ?? $contact->channel_connection_id,
            'lead_id' => $lead?->id ?? $contact->lead_id,
            'external_user_id' => $externalUserId ?? $contact->external_user_id,
            'email' => $email ?? $contact->email,
            'phone' => $phone ?? $contact->phone,
            'display_name' => $displayName ?? $contact->display_name,
        ]);
        $contact->save();

        return $contact;
    }
}
