<?php

namespace Tests\Unit\Models;

use App\Models\Lead;
use Tests\TestCase;

class LeadStatusAndContactTest extends TestCase
{
    public function test_manually_assignable_statuses_excludes_won_by_default(): void
    {
        $statuses = Lead::manuallyAssignableStatuses();

        $this->assertArrayHasKey('new', $statuses);
        $this->assertArrayHasKey('lost', $statuses);
        $this->assertArrayNotHasKey('won', $statuses);
    }

    public function test_manually_assignable_statuses_includes_won_when_current_status_is_won(): void
    {
        $statuses = Lead::manuallyAssignableStatuses('won');

        $this->assertArrayHasKey('won', $statuses);
    }

    public function test_can_manually_transition_to_rejects_unknown_status(): void
    {
        $lead = new Lead(['status' => 'new']);

        $this->assertFalse($lead->canManuallyTransitionTo('archived'));
    }

    public function test_can_manually_transition_to_rejects_won_from_non_won_status(): void
    {
        $lead = new Lead(['status' => 'qualified']);

        $this->assertFalse($lead->canManuallyTransitionTo('won'));
    }

    public function test_can_manually_transition_to_allows_won_when_already_won(): void
    {
        $lead = new Lead(['status' => 'won']);

        $this->assertTrue($lead->canManuallyTransitionTo('won'));
    }

    /**
     * @dataProvider allowedTransitionProvider
     */
    public function test_can_manually_transition_to_allows_valid_statuses(string $from, string $to): void
    {
        $lead = new Lead(['status' => $from]);

        $this->assertTrue($lead->canManuallyTransitionTo($to));
    }

    public static function allowedTransitionProvider(): array
    {
        return [
            'new to contacted' => ['new', 'contacted'],
            'contacted to qualified' => ['contacted', 'qualified'],
            'qualified to proposal_sent' => ['qualified', 'proposal_sent'],
            'proposal_sent to lost' => ['proposal_sent', 'lost'],
        ];
    }

    public function test_status_label_returns_known_label(): void
    {
        $lead = new Lead(['status' => 'proposal_sent']);

        $this->assertSame('Proposal Sent', $lead->statusLabel());
    }

    public function test_status_label_falls_back_for_unknown_status(): void
    {
        $lead = new Lead(['status' => 'custom_status']);

        $this->assertSame('Custom status', $lead->statusLabel());
    }

    public function test_whats_app_url_strips_non_digits(): void
    {
        $lead = new Lead(['phone' => '+1 (555) 123-4567']);

        $this->assertSame('https://wa.me/15551234567', $lead->whatsAppUrl());
    }

    public function test_whats_app_url_returns_null_without_phone(): void
    {
        $lead = new Lead(['phone' => null]);

        $this->assertNull($lead->whatsAppUrl());
    }

    public function test_whats_app_url_returns_null_when_no_digits_remain(): void
    {
        $lead = new Lead(['phone' => 'abc']);

        $this->assertNull($lead->whatsAppUrl());
    }

    public function test_call_url_preserves_leading_plus(): void
    {
        $lead = new Lead(['phone' => '+1 (555) 123-4567']);

        $this->assertSame('tel:+15551234567', $lead->callUrl());
    }

    public function test_call_url_returns_null_without_phone(): void
    {
        $lead = new Lead(['phone' => '']);

        $this->assertNull($lead->callUrl());
    }

    public function test_email_url_returns_mailto_link(): void
    {
        $lead = new Lead(['email' => 'lead@example.com']);

        $this->assertSame('mailto:lead@example.com', $lead->emailUrl());
    }

    public function test_email_url_returns_null_without_email(): void
    {
        $lead = new Lead(['email' => null]);

        $this->assertNull($lead->emailUrl());
    }
}
