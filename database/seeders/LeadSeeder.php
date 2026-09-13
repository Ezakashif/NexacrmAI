<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\Scopes\CompanyScope;
use App\Models\User;
use App\Support\CurrentCompany;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Approximate monthly volumes for a gently rising pipeline curve.
     * Keys are months relative to "now" (0 = current month).
     *
     * @var array<int, int>
     */
    protected array $monthlyCounts = [
        -5 => 8,
        -4 => 11,
        -3 => 9,
        -2 => 14,
        -1 => 17,
        0 => 13,
    ];

    public function run(): void
    {
        config(['tenancy.fail_closed_without_context' => false]);

        $admin = User::withoutGlobalScope(CompanyScope::class)->where('email', 'admin@example.com')->first()
            ?? User::withoutGlobalScope(CompanyScope::class)->whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->first();

        $sales = User::withoutGlobalScope(CompanyScope::class)->where('email', 'sales@example.com')->first()
            ?? User::withoutGlobalScope(CompanyScope::class)->whereHas('roles', fn ($q) => $q->where('slug', 'sales'))->first();

        if (! $admin) {
            $this->command?->warn('LeadSeeder skipped: no admin user found. Run DatabaseSeeder first.');

            return;
        }

        if ($admin->company_id) {
            app(CurrentCompany::class)->set($admin->company_id);
        }

        $assignees = collect([$admin, $sales])->filter()->unique('id')->values();
        $statusWeights = [
            'new' => 22,
            'contacted' => 20,
            'qualified' => 18,
            'proposal_sent' => 14,
            'won' => 16,
            'lost' => 10,
        ];

        $sortOrders = array_fill_keys(array_keys(Lead::STATUSES), 0);
        $created = 0;

        foreach ($this->monthlyCounts as $offset => $count) {
            $monthStart = now()->startOfMonth()->addMonths($offset)->startOfDay();
            $monthEnd = $monthStart->copy()->endOfMonth();

            if ($offset === 0) {
                $monthEnd = now()->copy()->subHour();
            }

            if ($monthEnd->lessThan($monthStart)) {
                $monthEnd = $monthStart->copy()->addHours(6);
            }

            for ($i = 0; $i < $count; $i++) {
                $status = $this->weightedStatus($statusWeights);
                $createdAt = Carbon::createFromTimestamp(
                    fake()->numberBetween($monthStart->timestamp, max($monthStart->timestamp, $monthEnd->timestamp))
                );

                if ($offset <= -3 && fake()->boolean(35)) {
                    $status = fake()->randomElement(['won', 'lost', 'proposal_sent']);
                } elseif ($offset >= -1 && fake()->boolean(40)) {
                    $status = fake()->randomElement(['new', 'contacted', 'qualified']);
                }

                $assignee = fake()->boolean(12)
                    ? null
                    : $assignees->random();

                Lead::factory()
                    ->status($status, $sortOrders[$status]++)
                    ->state([
                        'company_id' => $admin->company_id,
                        'created_by' => $admin->id,
                        'assigned_to' => $assignee?->id,
                        'follow_up_date' => $this->followUpFor($status, $createdAt),
                        'created_at' => $createdAt,
                        'updated_at' => (clone $createdAt)->addDays(fake()->numberBetween(0, 12)),
                    ])
                    ->create();

                $created++;
            }
        }

        $this->command?->info("Seeded {$created} leads across the last six months.");
    }

    /**
     * @param  array<string, int>  $weights
     */
    protected function weightedStatus(array $weights): string
    {
        $pick = fake()->numberBetween(1, array_sum($weights));
        $running = 0;

        foreach ($weights as $status => $weight) {
            $running += $weight;
            if ($pick <= $running) {
                return $status;
            }
        }

        return 'new';
    }

    protected function followUpFor(string $status, Carbon $createdAt): ?Carbon
    {
        if (in_array($status, ['won', 'lost'], true)) {
            return null;
        }

        if (! fake()->boolean(65)) {
            return null;
        }

        return Carbon::parse(
            fake()->dateTimeBetween($createdAt, $createdAt->copy()->addDays(45))
        )->startOfDay();
    }
}
