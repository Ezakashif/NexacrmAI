<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    /**
     * Seed the three public marketing plans (idempotent).
     */
    public function run(): void
    {
        $definitions = [
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'short_description' => 'For small teams getting organized.',
                'description' => 'Core CRM for small teams: leads, customers, tasks, and email support.',
                'monthly_price' => 29,
                'yearly_price' => 23,
                'price_cents' => 2900,
                'max_users' => 5,
                'max_leads' => 500,
                'max_customers' => 200,
                'trial_days' => 14,
                'is_free' => false,
                'is_featured' => false,
                'is_default' => true,
                'sort_order' => 1,
                'features' => [
                    ['key' => 'lead_customer_management', 'name' => 'Lead & customer management'],
                    ['key' => 'task_boards', 'name' => 'Task boards'],
                    ['key' => 'email_support', 'name' => 'Email support'],
                ],
            ],
            [
                'slug' => 'professional',
                'name' => 'Professional',
                'short_description' => 'For growing sales teams that need full pipeline visibility.',
                'description' => 'Full pipeline visibility with kanban, reports, CSV tools, and priority support.',
                'monthly_price' => 79,
                'yearly_price' => 63,
                'price_cents' => 7900,
                'max_users' => 25,
                'max_leads' => 5000,
                'max_customers' => 2000,
                'trial_days' => 14,
                'is_free' => false,
                'is_featured' => true,
                'is_default' => false,
                'sort_order' => 2,
                'features' => [
                    ['key' => 'kanban_pipelines', 'name' => 'Kanban pipelines'],
                    ['key' => 'reports_analytics', 'name' => 'Reports & analytics'],
                    ['key' => 'csv_import_export', 'name' => 'CSV import / export'],
                    ['key' => 'priority_support', 'name' => 'Priority support'],
                ],
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'short_description' => 'For multi-team organizations that need control at scale.',
                'description' => 'Unlimited scale with advanced roles, activity logs, and dedicated onboarding.',
                'monthly_price' => 149,
                'yearly_price' => 119,
                'price_cents' => 14900,
                'max_users' => null,
                'max_leads' => null,
                'max_customers' => null,
                'trial_days' => 0,
                'is_free' => false,
                'is_featured' => false,
                'is_default' => false,
                'sort_order' => 3,
                'features' => [
                    ['key' => 'role_permissions', 'name' => 'Role & permission management'],
                    ['key' => 'activity_logs', 'name' => 'Activity logs'],
                    ['key' => 'dedicated_onboarding', 'name' => 'Dedicated onboarding'],
                    ['key' => 'sla_sso', 'name' => 'SLA & SSO (coming soon)'],
                ],
            ],
        ];

        DB::transaction(function () use ($definitions): void {
            // Prefer marketing names: retire legacy "growth" if present.
            Plan::withTrashed()
                ->where('slug', 'growth')
                ->get()
                ->each(function (Plan $plan): void {
                    if (! $plan->trashed()) {
                        $plan->delete();
                    }
                });

            foreach ($definitions as $definition) {
                if ($definition['is_default']) {
                    Plan::query()->where('is_default', true)->update(['is_default' => false]);
                }

                $plan = Plan::withTrashed()->updateOrCreate(
                    ['slug' => $definition['slug']],
                    [
                        'name' => $definition['name'],
                        'short_description' => $definition['short_description'],
                        'description' => $definition['description'],
                        'monthly_price' => $definition['monthly_price'],
                        'yearly_price' => $definition['yearly_price'],
                        'price_cents' => $definition['price_cents'],
                        'max_users' => $definition['max_users'],
                        'max_leads' => $definition['max_leads'],
                        'max_customers' => $definition['max_customers'],
                        'currency' => 'USD',
                        'billing_cycle' => 'both',
                        'trial_days' => $definition['trial_days'],
                        'is_free' => $definition['is_free'],
                        'is_featured' => $definition['is_featured'],
                        'is_public' => true,
                        'is_active' => true,
                        'is_default' => $definition['is_default'],
                        'sort_order' => $definition['sort_order'],
                        'deleted_at' => null,
                    ],
                );

                $plan->features()->delete();
                foreach ($definition['features'] as $index => $feature) {
                    $plan->features()->create([
                        'feature_key' => $feature['key'],
                        'feature_name' => $feature['name'],
                        'feature_type' => 'boolean',
                        'feature_value' => null,
                        'sort_order' => $index + 1,
                        'is_highlighted' => false,
                    ]);
                }

                $plan->limits()->delete();
                foreach ([
                    ['users', 'Users', $definition['max_users'], 1],
                    ['leads', 'Leads', $definition['max_leads'], 2],
                    ['customers', 'Customers', $definition['max_customers'], 3],
                ] as [$key, $name, $value, $order]) {
                    $plan->limits()->create([
                        'limit_key' => $key,
                        'limit_name' => $name,
                        'limit_value' => $value === null ? null : (string) $value,
                        'unit' => 'count',
                        'description' => null,
                        'sort_order' => $order,
                    ]);
                }
            }
        });

        $this->command?->info('Seeded plans: '.implode(', ', array_column($definitions, 'name')));
    }
}
