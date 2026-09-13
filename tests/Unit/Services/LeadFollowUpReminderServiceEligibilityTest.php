<?php

namespace Tests\Unit\Services;

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadFollowUpReminderService;
use Carbon\Carbon;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class LeadFollowUpReminderServiceEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private LeadFollowUpReminderService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        config([
            'lead_reminders.default_follow_up_time' => '09:00',
            'lead_reminders.tiers.hours_before.hours' => 2,
            'lead_reminders.tiers.overdue.repeat_days' => 1,
        ]);

        $this->service = app(LeadFollowUpReminderService::class);
    }

    /**
     * @dataProvider ineligibleLeadProvider
     */
    public function test_is_eligible_returns_false_for_ineligible_leads(
        string $tier,
        callable $leadFactory,
    ): void {
        $lead = $leadFactory($this);

        $this->assertFalse($this->service->isEligible($lead, $tier));
    }

    public static function ineligibleLeadProvider(): array
    {
        return [
            'day_before without assignee' => [
                'day_before',
                fn (self $test) => $test->makeLead(['assigned_to' => null, 'follow_up_date' => today()->addDay()]),
            ],
            'day_before without follow_up_date' => [
                'day_before',
                fn (self $test) => $test->makeLead(['follow_up_date' => null]),
            ],
            'day_before for won lead' => [
                'day_before',
                fn (self $test) => $test->makeLead(['status' => 'won', 'follow_up_date' => today()->addDay()]),
            ],
            'day_before wrong date' => [
                'day_before',
                fn (self $test) => $test->makeLead(['follow_up_date' => today()->addDays(2)]),
            ],
            'day_before already sent' => [
                'day_before',
                fn (self $test) => $test->makeLead([
                    'follow_up_date' => today()->addDay(),
                    'follow_up_reminders_sent' => ['day_before' => now()->toIso8601String()],
                ]),
            ],
            'hours_before outside window' => [
                'hours_before',
                function (self $test) {
                    $test->travelTo(now()->setTime(12, 0, 0));

                    return $test->makeLead(['follow_up_date' => today()]);
                },
            ],
            'due already sent' => [
                'due',
                fn (self $test) => $test->makeLead([
                    'follow_up_date' => today(),
                    'follow_up_reminders_sent' => ['due' => now()->toIso8601String()],
                ]),
            ],
            'due future follow_up' => [
                'due',
                fn (self $test) => $test->makeLead(['follow_up_date' => today()->addDay()]),
            ],
            'overdue without prior due reminder' => [
                'overdue',
                fn (self $test) => $test->makeLead(['follow_up_date' => today()->subDay()]),
            ],
            'overdue before repeat interval' => [
                'overdue',
                fn (self $test) => $test->makeLead([
                    'follow_up_date' => today()->subDays(2),
                    'follow_up_reminders_sent' => [
                        'due' => now()->subDays(2)->toIso8601String(),
                        'overdue' => now()->subHours(2)->toIso8601String(),
                    ],
                ]),
            ],
            'unknown tier' => [
                'invalid',
                fn (self $test) => $test->makeLead(['follow_up_date' => today()]),
            ],
        ];
    }

    /**
     * @dataProvider eligibleLeadProvider
     */
    public function test_is_eligible_returns_true_for_matching_tiers(
        string $tier,
        callable $leadFactory,
        ?callable $timeTravel = null,
    ): void {
        if ($timeTravel) {
            $timeTravel($this);
        }

        $lead = $leadFactory($this);

        $this->assertTrue($this->service->isEligible($lead, $tier));
    }

    public static function eligibleLeadProvider(): array
    {
        return [
            'day_before tomorrow' => [
                'day_before',
                fn (self $test) => $test->makeLead(['follow_up_date' => today()->addDay()]),
            ],
            'hours_before inside window' => [
                'hours_before',
                fn (self $test) => $test->makeLead(['follow_up_date' => today()]),
                fn (self $test) => $test->travelTo(now()->setTime(7, 15, 0)),
            ],
            'due today' => [
                'due',
                fn (self $test) => $test->makeLead(['follow_up_date' => today()]),
            ],
            'due past date' => [
                'due',
                fn (self $test) => $test->makeLead(['follow_up_date' => today()->subDay()]),
            ],
            'overdue after due reminder and repeat interval' => [
                'overdue',
                fn (self $test) => $test->makeLead([
                    'follow_up_date' => today()->subDays(2),
                    'follow_up_reminders_sent' => [
                        'due' => now()->subDays(2)->toIso8601String(),
                        'overdue' => now()->subDay()->toIso8601String(),
                    ],
                ]),
            ],
        ];
    }

    public function test_is_inside_hours_before_window_matches_configured_window(): void
    {
        $lead = $this->makeLead(['follow_up_date' => today()]);

        $this->travelTo(Carbon::parse(today()->toDateString().' 07:30:00'));
        $this->assertTrue($this->service->isInsideHoursBeforeWindow($lead));

        $this->travelTo(Carbon::parse(today()->toDateString().' 10:00:00'));
        $this->assertFalse($this->service->isInsideHoursBeforeWindow($lead));
    }

    public function test_eligible_scope_excludes_leads_with_sent_tier(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Lead::factory()->assignedTo($user)->create([
            'follow_up_date' => today(),
            'follow_up_reminders_sent' => ['due' => now()->toIso8601String()],
        ]);

        $eligible = Lead::query()->eligibleForFollowUpReminderTier('due')->pluck('id');

        $this->assertCount(0, $eligible);
    }

    public function test_assert_valid_tier_rejects_unknown_tier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown follow-up reminder tier [bogus].');

        $reflection = new \ReflectionMethod($this->service, 'assertValidTier');
        $reflection->setAccessible(true);
        $reflection->invoke($this->service, 'bogus');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeLead(array $overrides = []): Lead
    {
        $user = User::factory()->create(['status' => 'active']);

        return Lead::factory()->assignedTo($user)->make(array_merge([
            'status' => 'new',
            'follow_up_reminders_sent' => null,
        ], $overrides));
    }
}
