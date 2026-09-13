<?php

namespace App\Services\Channels;

use App\Models\ChannelContact;
use App\Models\ChannelWebhookEvent;
use App\Models\Lead;
use App\Models\LeadChannelMeta;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Channels\DTOs\InboundLeadDTO;
use App\Services\CrmNotificationDispatcher;
use App\Services\PlanLimitService;
use Illuminate\Support\Facades\DB;

class LeadProcessingService
{
    public function __construct(
        protected LeadMatchingService $matcher,
        protected PlanLimitService $planLimits,
        protected CrmNotificationDispatcher $notifications,
    ) {}

    /**
     * @return array{lead: Lead, contact: ChannelContact, created: bool}
     */
    public function process(InboundLeadDTO $dto, ChannelWebhookEvent $event): array
    {
        return DB::transaction(function () use ($dto, $event) {
            $companyId = (int) $event->company_id;
            $existing = $this->matcher->matchLead($dto, $companyId);
            $created = false;

            if ($existing) {
                $lead = $existing;
                $lead->fill(array_filter([
                    'name' => $dto->name,
                    'email' => $dto->email,
                    'phone' => $dto->phone,
                    'company' => $dto->companyName,
                ], fn ($value) => $value !== null && $value !== ''));

                if (filled($dto->notes) && blank($lead->notes)) {
                    $lead->notes = $dto->notes;
                }

                $lead->save();
            } else {
                $company = \App\Models\Company::query()->findOrFail($companyId);

                $this->planLimits->assertCanAddLead($company);

                $sortOrder = Lead::query()->where('status', 'new')->max('sort_order') + 1;
                $createdById = $this->resolveCreatedByUserId($companyId);

                $lead = new Lead([
                    'created_by' => $createdById,
                    'assigned_to' => null,
                    'name' => $dto->name,
                    'email' => $dto->email,
                    'phone' => $dto->phone,
                    'company' => $dto->companyName,
                    'source' => $dto->sourceSlug(),
                    'status' => 'new',
                    'sort_order' => $sortOrder,
                    'notes' => $dto->notes,
                ]);
                $lead->company_id = $companyId;
                $lead->save();
                $created = true;

                ActivityLogger::log('lead.created_via_channel', $lead, [
                    'provider' => $dto->provider->value,
                    'name' => $lead->name,
                    'webhook_event_id' => $event->id,
                ], $createdById);

                $this->notifications->websiteLeadReceived($lead);
            }

            $contact = $this->matcher->upsertContact(
                provider: $dto->provider,
                companyId: $companyId,
                connection: $event->connection,
                lead: $lead,
                externalUserId: $dto->externalUserId,
                email: $dto->email,
                phone: $dto->phone,
                displayName: $dto->name,
            );

            $this->storeChannelMeta($lead, $dto, $event);

            ActivityLogger::log('channel.lead_processed', $lead, [
                'provider' => $dto->provider->value,
                'created' => $created,
                'webhook_event_id' => $event->id,
            ]);

            return compact('lead', 'contact', 'created');
        });
    }

    protected function storeChannelMeta(Lead $lead, InboundLeadDTO $dto, ChannelWebhookEvent $event): void
    {
        $campaign = $dto->campaign;

        LeadChannelMeta::query()->updateOrCreate(
            [
                'lead_id' => $lead->id,
                'provider' => $dto->provider,
            ],
            [
                'company_id' => $lead->company_id,
                'channel_connection_id' => $event->channel_connection_id,
                'channel_webhook_event_id' => $event->id,
                'campaign_id' => $campaign['campaign_id'] ?? null,
                'campaign_name' => $campaign['campaign_name'] ?? null,
                'adset_id' => $campaign['adset_id'] ?? null,
                'ad_id' => $campaign['ad_id'] ?? null,
                'ad_name' => $campaign['ad_name'] ?? null,
                'form_id' => $campaign['form_id'] ?? null,
                'form_name' => $campaign['form_name'] ?? null,
                'page_id' => $campaign['page_id'] ?? null,
                'raw' => [
                    'campaign' => $campaign,
                    'meta' => $dto->meta,
                    'external_lead_id' => $dto->externalLeadId,
                ],
            ],
        );
    }

    protected function resolveCreatedByUserId(int $companyId): int
    {
        $userId = User::withoutCompanyScope()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->where('is_super_admin', false)
            ->orderBy('id')
            ->value('id');

        if ($userId === null) {
            throw new \RuntimeException("No active user available to own channel leads for company [{$companyId}].");
        }

        return (int) $userId;
    }
}
