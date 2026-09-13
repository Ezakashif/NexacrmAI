<?php

namespace Tests\Unit\Notifications;

use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadFollowUpDue;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadFollowUpDueCopyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    /**
     * @dataProvider tierCopyProvider
     *
     * @param  array{0: string, 1: string}  $expected
     */
    public function test_to_array_exposes_subject_and_message_from_copy_helpers(
        string $tier,
        array $expected,
        ?callable $leadFactory = null,
    ): void {
        $user = User::factory()->create();
        $lead = $leadFactory
            ? $leadFactory($this)
            : Lead::factory()->create(['follow_up_date' => today(), 'name' => 'Acme Lead']);

        $payload = (new LeadFollowUpDue($lead, $tier))->toArray($user);

        $this->assertSame($expected[0], $payload['subject']);
        $this->assertSame($expected[1], $payload['message']);
        $this->assertSame($tier, $payload['tier']);
    }

    public static function tierCopyProvider(): array
    {
        return [
            'day_before' => [
                'day_before',
                [
                    'Follow-up tomorrow',
                    'Reminder: follow-up for Acme Lead is scheduled for tomorrow ('.today()->format('M j, Y').').',
                ],
            ],
            'hours_before' => [
                'hours_before',
                [
                    'Follow-up in 2 hours',
                    'Reminder: follow-up for Acme Lead is coming up in about 2 hours ('.today()->format('M j, Y').').',
                ],
            ],
            'overdue tier' => [
                'overdue',
                [
                    'Follow-up still overdue',
                    'Follow-up for Acme Lead remains overdue since '.today()->subDay()->format('M j, Y').'.',
                ],
                fn (self $test) => Lead::factory()->create([
                    'name' => 'Acme Lead',
                    'follow_up_date' => today()->subDay(),
                ]),
            ],
            'due today' => [
                'due',
                [
                    'Follow-up due today',
                    'You have a lead follow-up that is due today ('.today()->format('M j, Y').').',
                ],
            ],
            'default overdue copy when tier is due but date is past' => [
                'due',
                [
                    'Follow-up overdue',
                    'You have a lead follow-up that was due on '.today()->subDays(2)->format('M j, Y').'.',
                ],
                fn (self $test) => Lead::factory()->create([
                    'name' => 'Acme Lead',
                    'follow_up_date' => today()->subDays(2),
                ]),
            ],
        ];
    }
}
