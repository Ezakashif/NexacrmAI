<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use Tests\TestCase;

class CompanySubscriptionStateTest extends TestCase
{
    public function test_is_active_checks_company_status(): void
    {
        $active = new Company(['status' => Company::STATUS_ACTIVE]);
        $suspended = new Company(['status' => Company::STATUS_SUSPENDED]);

        $this->assertTrue($active->isActive());
        $this->assertFalse($suspended->isActive());
    }

    public function test_is_on_trial_when_subscription_is_trial_and_not_expired(): void
    {
        $trial = new Company([
            'subscription_status' => Company::SUBSCRIPTION_TRIAL,
            'trial_ends_at' => now()->addDays(7),
        ]);

        $this->assertTrue($trial->isOnTrial());
    }

    public function test_is_on_trial_when_trial_has_no_end_date(): void
    {
        $trial = new Company([
            'subscription_status' => Company::SUBSCRIPTION_TRIAL,
            'trial_ends_at' => null,
        ]);

        $this->assertTrue($trial->isOnTrial());
    }

    public function test_is_not_on_trial_when_subscription_is_active(): void
    {
        $company = new Company([
            'subscription_status' => Company::SUBSCRIPTION_ACTIVE,
            'trial_ends_at' => null,
        ]);

        $this->assertFalse($company->isOnTrial());
    }

    public function test_is_subscription_expired_when_status_is_expired(): void
    {
        $company = new Company([
            'subscription_status' => Company::SUBSCRIPTION_EXPIRED,
            'trial_ends_at' => null,
        ]);

        $this->assertTrue($company->isSubscriptionExpired());
    }

    public function test_is_subscription_expired_when_trial_end_date_is_past(): void
    {
        $company = new Company([
            'subscription_status' => Company::SUBSCRIPTION_TRIAL,
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->assertTrue($company->isSubscriptionExpired());
    }

    public function test_is_not_subscription_expired_for_active_subscription(): void
    {
        $company = new Company([
            'subscription_status' => Company::SUBSCRIPTION_ACTIVE,
            'trial_ends_at' => null,
        ]);

        $this->assertFalse($company->isSubscriptionExpired());
    }

    public function test_is_trial_expired_when_trial_end_date_is_past(): void
    {
        $company = new Company([
            'subscription_status' => Company::SUBSCRIPTION_TRIAL,
            'trial_ends_at' => now()->subHour(),
        ]);

        $this->assertTrue($company->isTrialExpired());
    }

    public function test_is_not_trial_expired_when_trial_is_still_active(): void
    {
        $company = new Company([
            'subscription_status' => Company::SUBSCRIPTION_TRIAL,
            'trial_ends_at' => now()->addDay(),
        ]);

        $this->assertFalse($company->isTrialExpired());
    }

    public function test_expired_access_message_for_expired_trial(): void
    {
        $company = new Company([
            'subscription_status' => Company::SUBSCRIPTION_TRIAL,
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->assertSame('Your free trial has expired.', $company->expiredAccessMessage());
    }

    public function test_expired_access_message_for_expired_subscription(): void
    {
        $company = new Company([
            'subscription_status' => Company::SUBSCRIPTION_EXPIRED,
            'trial_ends_at' => null,
        ]);

        $this->assertSame(
            'Your company subscription has expired. Please contact support to renew access.',
            $company->expiredAccessMessage(),
        );
    }

    public function test_expired_access_message_for_expired_status_with_past_trial(): void
    {
        $company = new Company([
            'subscription_status' => Company::SUBSCRIPTION_EXPIRED,
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->assertSame('Your free trial has expired.', $company->expiredAccessMessage());
    }

    public function test_is_default_checks_slug(): void
    {
        $default = new Company(['slug' => Company::DEFAULT_SLUG]);
        $other = new Company(['slug' => 'acme-corp']);

        $this->assertTrue($default->isDefault());
        $this->assertFalse($other->isDefault());
    }
}
