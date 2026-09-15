<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Scopes\CompanyScope;
use App\Models\Task;
use App\Models\User;
use App\Services\PermissionRegistry;
use App\Services\RbacRoleSynchronizer;
use App\Support\CurrentCompany;
use App\Support\DemoEnvironment;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Polished fictional demo tenant for screenshots, prospect demos, and marketing.
 *
 * Boundaries:
 * - Only touches the company identified by {@see self::COMPANY_SLUG}.
 * - Never deletes or updates records belonging to other companies.
 * - Safe to re-run: upserts deterministic demo rows (no tenant wipe).
 *
 * Run explicitly (also requires DEMO_SEED_PASSWORD):
 *   php artisan db:seed --class=DemoDataSeeder
 *
 * Or opt in from DatabaseSeeder:
 *   DEMO_SEED=true php artisan db:seed
 *
 * Never called by a normal buyer install.
 */
class DemoDataSeeder extends Seeder
{
    /** Centralized demo company identity — change here only. */
    public const COMPANY_SLUG = 'northstar-solutions';

    public const COMPANY_NAME = 'Northstar Solutions';

    public const COMPANY_EMAIL = 'hello@northstar.demo.nexacrm.test';

    public const COMPANY_PHONE = '+1-555-014-2200';

    public const COMPANY_TIMEZONE = 'America/Chicago';

    public const COMPANY_CURRENCY = 'USD';

    /** @var array<string, array{name: string, email: string, role: string, legacy_role: string}> */
    public const USERS = [
        'admin' => [
            'name' => 'Avery Quinn',
            'email' => 'admin@demo.nexacrm.test',
            'role' => 'admin',
            'legacy_role' => 'admin',
        ],
        'manager' => [
            'name' => 'Jordan Hale',
            'email' => 'manager@demo.nexacrm.test',
            'role' => 'sales_manager',
            'legacy_role' => 'user',
        ],
        'sales' => [
            'name' => 'Casey Morgan',
            'email' => 'sales@demo.nexacrm.test',
            'role' => 'sales',
            'legacy_role' => 'user',
        ],
    ];

    public const SALES_MANAGER_ROLE_SLUG = 'sales_manager';

    public const SALES_MANAGER_ROLE_NAME = 'Sales Manager';

    /**
     * Deterministic fictional leads keyed by stable email.
     * status won => will get a matching customer.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $leadsBlueprint = [
        [
            'key' => 'brightpath',
            'name' => 'Elena Vasquez',
            'email' => 'elena.vasquez@brightpath.demo.nexacrm.test',
            'phone' => '+1-555-010-1001',
            'company' => 'BrightPath Consulting',
            'source' => 'website',
            'status' => 'new',
            'value' => 4200,
            'months_ago' => 0,
            'assignee' => 'sales',
            'follow_up_offset_days' => 2,
            'notes' => 'Requested a walkthrough of lead pipeline and task reminders.',
        ],
        [
            'key' => 'vertex',
            'name' => 'Marcus Chen',
            'email' => 'marcus.chen@vertex.demo.nexacrm.test',
            'phone' => '+1-555-010-1002',
            'company' => 'Vertex Retail',
            'source' => 'linkedin',
            'status' => 'contacted',
            'value' => 8600,
            'months_ago' => 0,
            'assignee' => 'sales',
            'follow_up_offset_days' => 1,
            'notes' => 'Interested in multi-user CRM for a regional retail team.',
        ],
        [
            'key' => 'bluepeak',
            'name' => 'Priya Nair',
            'email' => 'priya.nair@bluepeak.demo.nexacrm.test',
            'phone' => '+1-555-010-1003',
            'company' => 'BluePeak Logistics',
            'source' => 'referral',
            'status' => 'qualified',
            'value' => 15200,
            'months_ago' => 1,
            'assignee' => 'manager',
            'follow_up_offset_days' => 3,
            'notes' => 'Needs reporting for regional freight sales coverage.',
        ],
        [
            'key' => 'novatech',
            'name' => 'Owen Brooks',
            'email' => 'owen.brooks@novatech.demo.nexacrm.test',
            'phone' => '+1-555-010-1004',
            'company' => 'NovaTech Solutions',
            'source' => 'website',
            'status' => 'proposal_sent',
            'value' => 19800,
            'months_ago' => 1,
            'assignee' => 'manager',
            'follow_up_offset_days' => 0,
            'notes' => 'Proposal sent for Professional plan with onboarding package.',
        ],
        [
            'key' => 'cedar',
            'name' => 'Hannah Ortiz',
            'email' => 'hannah.ortiz@cedar.demo.nexacrm.test',
            'phone' => '+1-555-010-1005',
            'company' => 'Cedar & Co.',
            'source' => 'facebook',
            'status' => 'new',
            'value' => 3100,
            'months_ago' => 0,
            'assignee' => 'sales',
            'follow_up_offset_days' => 4,
            'notes' => 'Inbound form: small boutique agency exploring a simple CRM.',
        ],
        [
            'key' => 'summit',
            'name' => 'Liam Porter',
            'email' => 'liam.porter@summit.demo.nexacrm.test',
            'phone' => '+1-555-010-1006',
            'company' => 'Summit Digital',
            'source' => 'cold_call',
            'status' => 'contacted',
            'value' => 7400,
            'months_ago' => 2,
            'assignee' => 'sales',
            'follow_up_offset_days' => -1,
            'notes' => 'Had intro call; waiting on stakeholder availability.',
        ],
        [
            'key' => 'urbanedge',
            'name' => 'Sofia Rahman',
            'email' => 'sofia.rahman@urbanedge.demo.nexacrm.test',
            'phone' => '+1-555-010-1007',
            'company' => 'UrbanEdge Services',
            'source' => 'whatsapp',
            'status' => 'qualified',
            'value' => 11200,
            'months_ago' => 2,
            'assignee' => 'manager',
            'follow_up_offset_days' => 5,
            'notes' => 'Qualified for annual billing; demo scheduled.',
        ],
        [
            'key' => 'primeworks',
            'name' => 'Noah Ellis',
            'email' => 'noah.ellis@primeworks.demo.nexacrm.test',
            'phone' => '+1-555-010-1008',
            'company' => 'PrimeWorks',
            'source' => 'referral',
            'status' => 'lost',
            'value' => 9500,
            'months_ago' => 3,
            'assignee' => 'sales',
            'follow_up_offset_days' => null,
            'notes' => 'Chose an incumbent vendor after budget freeze.',
        ],
        [
            'key' => 'clearview',
            'name' => 'Amelia Grant',
            'email' => 'amelia.grant@clearview.demo.nexacrm.test',
            'phone' => '+1-555-010-1009',
            'company' => 'ClearView Systems',
            'source' => 'linkedin',
            'status' => 'won',
            'value' => 16400,
            'months_ago' => 2,
            'assignee' => 'manager',
            'follow_up_offset_days' => null,
            'notes' => 'Converted after a two-week pilot with sales team.',
            'customer_address' => '1840 Market Street, Suite 410, Austin, TX 78701',
        ],
        [
            'key' => 'greenline',
            'name' => 'Diego Alvarez',
            'email' => 'diego.alvarez@greenline.demo.nexacrm.test',
            'phone' => '+1-555-010-1010',
            'company' => 'Greenline Distribution',
            'source' => 'website',
            'status' => 'won',
            'value' => 12800,
            'months_ago' => 3,
            'assignee' => 'sales',
            'follow_up_offset_days' => null,
            'notes' => 'Signed annual plan; onboarding complete.',
            'customer_address' => '92 Harbor Way, Portland, OR 97209',
        ],
        [
            'key' => 'harbor',
            'name' => 'Riley Thompson',
            'email' => 'riley.thompson@harbor.demo.nexacrm.test',
            'phone' => '+1-555-010-1011',
            'company' => 'Harbor & Field',
            'source' => 'referral',
            'status' => 'won',
            'value' => 9100,
            'months_ago' => 4,
            'assignee' => 'manager',
            'follow_up_offset_days' => null,
            'notes' => 'Converted from proposal stage in Q1.',
            'customer_address' => '55 Lakeview Drive, Madison, WI 53703',
        ],
        [
            'key' => 'skylight',
            'name' => 'Maya Singh',
            'email' => 'maya.singh@skylight.demo.nexacrm.test',
            'phone' => '+1-555-010-1012',
            'company' => 'Skylight Media',
            'source' => 'facebook',
            'status' => 'won',
            'value' => 6700,
            'months_ago' => 5,
            'assignee' => 'sales',
            'follow_up_offset_days' => null,
            'notes' => 'Agency account; uses CRM for client lead intake.',
            'customer_address' => '701 Creative Lane, Denver, CO 80202',
        ],
        [
            'key' => 'ironclad',
            'name' => 'Ethan Brooks',
            'email' => 'ethan.brooks@ironclad.demo.nexacrm.test',
            'phone' => '+1-555-010-1013',
            'company' => 'Ironclad Facilities',
            'source' => 'cold_call',
            'status' => 'won',
            'value' => 14300,
            'months_ago' => 1,
            'assignee' => 'admin',
            'follow_up_offset_days' => null,
            'notes' => 'Facilities services firm; won on reporting clarity.',
            'customer_address' => '1200 Industrial Blvd, Nashville, TN 37210',
        ],
        [
            'key' => 'willow',
            'name' => 'Chloe Bennett',
            'email' => 'chloe.bennett@willow.demo.nexacrm.test',
            'phone' => '+1-555-010-1014',
            'company' => 'Willow Creek Partners',
            'source' => 'website',
            'status' => 'won',
            'value' => 10500,
            'months_ago' => 2,
            'assignee' => 'manager',
            'follow_up_offset_days' => null,
            'notes' => 'Advisory firm; active customer since mid-quarter.',
            'customer_address' => '18 Oak Plaza, Charlotte, NC 28202',
        ],
        [
            'key' => 'pulse',
            'name' => 'Aaron Kim',
            'email' => 'aaron.kim@pulse.demo.nexacrm.test',
            'phone' => '+1-555-010-1015',
            'company' => 'Pulse Analytics',
            'source' => 'linkedin',
            'status' => 'won',
            'value' => 17900,
            'months_ago' => 0,
            'assignee' => 'admin',
            'follow_up_offset_days' => null,
            'notes' => 'Recently converted; expansion discussion next quarter.',
            'customer_address' => '400 Innovation Park, Seattle, WA 98109',
        ],
        [
            'key' => 'ridgeway',
            'name' => 'Natalie Shaw',
            'email' => 'natalie.shaw@ridgeway.demo.nexacrm.test',
            'phone' => '+1-555-010-1018',
            'company' => 'Ridgeway Health Group',
            'source' => 'website',
            'status' => 'won',
            'value' => 13600,
            'months_ago' => 3,
            'assignee' => 'manager',
            'follow_up_offset_days' => null,
            'notes' => 'Healthcare admin team; converted after security review.',
            'customer_address' => '88 Wellness Parkway, Minneapolis, MN 55401',
        ],
        [
            'key' => 'lumen',
            'name' => 'Grace Patel',
            'email' => 'grace.patel@lumen.demo.nexacrm.test',
            'phone' => '+1-555-010-1016',
            'company' => 'Lumen Studio',
            'source' => 'whatsapp',
            'status' => 'proposal_sent',
            'value' => 5800,
            'months_ago' => 0,
            'assignee' => 'sales',
            'follow_up_offset_days' => 2,
            'notes' => 'Creative studio comparing Starter vs Professional.',
        ],
        [
            'key' => 'anchor',
            'name' => 'Benjamin Cole',
            'email' => 'benjamin.cole@anchor.demo.nexacrm.test',
            'phone' => '+1-555-010-1017',
            'company' => 'Anchor Supply Co.',
            'source' => 'referral',
            'status' => 'lost',
            'value' => 8200,
            'months_ago' => 4,
            'assignee' => 'sales',
            'follow_up_offset_days' => null,
            'notes' => 'Lost to timing — revisit in six months.',
        ],
    ];

    /** @var array<string, User> */
    private array $users = [];

    /** @var array<string, Lead> */
    private array $leads = [];

    /** @var array<string, Customer> */
    private array $customers = [];

    private Company $company;

    public function run(): void
    {
        DemoEnvironment::requireSeedPassword();
        $this->assertDemoEmailsAreAvailable();

        config(['tenancy.fail_closed_without_context' => false]);

        app(PermissionRegistry::class)->sync();

        $this->company = $this->upsertCompany();
        app(CurrentCompany::class)->set($this->company);

        app(RbacRoleSynchronizer::class)->syncDefaultRolesForCompany($this->company);
        $this->upsertSalesManagerRole();

        $this->upsertUsers();
        $this->company->forceFill(['owner_id' => $this->users['admin']->id])->save();

        $this->upsertLeads();
        $this->upsertCustomersFromWonLeads();
        $this->upsertLeadActivities();
        $this->upsertTasks();
        $this->upsertActivityLogs();

        $this->command?->info(sprintf(
            'Demo tenant ready: %s (%s) — users=%d leads=%d customers=%d tasks=%d activities=%d',
            self::COMPANY_NAME,
            self::COMPANY_SLUG,
            count($this->users),
            count($this->leads),
            count($this->customers),
            Task::withoutGlobalScope(CompanyScope::class)->where('company_id', $this->company->id)->count(),
            LeadActivity::withoutGlobalScope(CompanyScope::class)->where('company_id', $this->company->id)->count(),
        ));
    }

    public static function demoPassword(): string
    {
        return DemoEnvironment::requireSeedPassword();
    }

    private function assertDemoEmailsAreAvailable(): void
    {
        $demoCompanyId = Company::withTrashed()->where('slug', self::COMPANY_SLUG)->value('id');

        foreach (self::USERS as $definition) {
            $user = User::withoutGlobalScope(CompanyScope::class)
                ->where('email', $definition['email'])
                ->first();

            if ($user === null) {
                continue;
            }

            if ($demoCompanyId === null || (int) $user->company_id !== (int) $demoCompanyId) {
                throw new RuntimeException(sprintf(
                    'Demo email %s already belongs to another company (id=%s). Aborting without moving or overwriting that user.',
                    $definition['email'],
                    $user->company_id ?? 'null',
                ));
            }
        }
    }

    private function upsertCompany(): Company
    {
        $plan = Plan::query()->where('slug', 'enterprise')->first()
            ?? Plan::query()->where('slug', 'professional')->first()
            ?? Plan::query()->where('is_default', true)->first();

        $company = Company::withTrashed()->firstOrNew(['slug' => self::COMPANY_SLUG]);

        if ($company->trashed()) {
            $company->restore();
        }

        $company->fill([
            'name' => self::COMPANY_NAME,
            'email' => self::COMPANY_EMAIL,
            'phone' => self::COMPANY_PHONE,
            'timezone' => self::COMPANY_TIMEZONE,
            'currency' => self::COMPANY_CURRENCY,
            'status' => Company::STATUS_ACTIVE,
            'subscription_status' => Company::SUBSCRIPTION_ACTIVE,
            'trial_ends_at' => null,
            'plan_id' => $plan?->id,
            'address_line_1' => '500 Commerce Avenue',
            'city' => 'Austin',
            'state' => 'TX',
            'postal_code' => '78701',
            'country' => 'US',
        ]);
        $company->save();

        return $company->fresh();
    }

    private function upsertSalesManagerRole(): void
    {
        $role = Role::withoutGlobalScope(CompanyScope::class)->firstOrNew([
            'company_id' => $this->company->id,
            'slug' => self::SALES_MANAGER_ROLE_SLUG,
        ]);

        $role->fill([
            'name' => self::SALES_MANAGER_ROLE_NAME,
            'description' => 'Sales leadership with team-wide lead and task visibility.',
            'is_system' => false,
            'company_id' => $this->company->id,
        ]);
        $role->save();

        $permissionSlugs = array_values(array_unique(array_merge(
            RbacSeeder::ROLE_PERMISSIONS['sales'],
            [
                'view_all.leads',
                'assign.leads',
                'view_all.tasks',
                'assign.tasks',
                'create.tasks',
                'view.activity_logs',
            ],
        )));

        $permissionIds = Permission::query()
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
    }

    private function upsertUsers(): void
    {
        $password = self::demoPassword();

        foreach (self::USERS as $key => $definition) {
            $user = User::withoutGlobalScope(CompanyScope::class)
                ->where('email', $definition['email'])
                ->first();

            if ($user !== null && (int) $user->company_id !== (int) $this->company->id) {
                throw new RuntimeException(sprintf(
                    'Demo email %s already belongs to another company (id=%s). Aborting without moving or overwriting that user.',
                    $definition['email'],
                    $user->company_id ?? 'null',
                ));
            }

            $user ??= User::withoutGlobalScope(CompanyScope::class)->firstOrNew([
                'email' => $definition['email'],
            ]);

            $user->forceFill([
                'name' => $definition['name'],
                'password' => $password,
                'email_verified_at' => $user->email_verified_at ?? now(),
                'role' => $definition['legacy_role'],
                'status' => 'active',
                'is_super_admin' => false,
                'company_id' => $this->company->id,
                'timezone' => self::COMPANY_TIMEZONE,
                'language' => 'en',
                'dashboard_tour_completed_at' => now(),
            ]);
            $user->save();

            $user->syncRolesBySlug([$definition['role']]);
            $this->users[$key] = $user->fresh();
        }
    }

    private function upsertLeads(): void
    {
        $sortOrders = array_fill_keys(array_keys(Lead::STATUSES), 0);

        foreach ($this->leadsBlueprint as $blueprint) {
            $assignee = $this->users[$blueprint['assignee']];
            $createdAt = now()
                ->subMonths((int) $blueprint['months_ago'])
                ->subDays(abs(crc32($blueprint['key'])) % 20)
                ->setTime(10 + (abs(crc32($blueprint['email'])) % 7), 15);

            $followUp = null;
            if ($blueprint['follow_up_offset_days'] !== null) {
                $followUp = now()->startOfDay()->addDays((int) $blueprint['follow_up_offset_days']);
            }

            $sortOrders[$blueprint['status']]++;

            $lead = Lead::withoutGlobalScope(CompanyScope::class)->firstOrNew([
                'company_id' => $this->company->id,
                'email' => $blueprint['email'],
            ]);

            $lead->fill([
                'created_by' => $assignee->id,
                'assigned_to' => $assignee->id,
                'name' => $blueprint['name'],
                'phone' => $blueprint['phone'],
                'company' => $blueprint['company'],
                'source' => $blueprint['source'],
                'status' => $blueprint['status'],
                'sort_order' => $sortOrders[$blueprint['status']],
                'estimated_value' => $blueprint['value'],
                'notes' => $blueprint['notes'],
                'follow_up_date' => $followUp,
                'company_id' => $this->company->id,
            ]);
            $lead->save();

            // Preserve timeline realism on first create; refresh timestamps on upsert.
            $lead->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt->copy()->addDays(min(12, max(1, (int) $blueprint['months_ago'] + 1))),
            ])->saveQuietly();

            $this->leads[$blueprint['key']] = $lead->fresh();
        }
    }

    private function upsertCustomersFromWonLeads(): void
    {
        foreach ($this->leadsBlueprint as $blueprint) {
            if ($blueprint['status'] !== 'won') {
                continue;
            }

            $lead = $this->leads[$blueprint['key']];
            $creator = $this->users[$blueprint['assignee']];

            $customer = Customer::withoutGlobalScope(CompanyScope::class)->firstOrNew([
                'company_id' => $this->company->id,
                'source_lead_id' => $lead->id,
            ]);

            // Also match by email if a prior run linked differently.
            if (! $customer->exists) {
                $byEmail = Customer::withoutGlobalScope(CompanyScope::class)
                    ->where('company_id', $this->company->id)
                    ->where('email', $blueprint['email'])
                    ->first();
                if ($byEmail) {
                    $customer = $byEmail;
                }
            }

            $customer->fill([
                'created_by' => $creator->id,
                'source_lead_id' => $lead->id,
                'name' => $blueprint['name'],
                'email' => $blueprint['email'],
                'phone' => $blueprint['phone'],
                'company_name' => $blueprint['company'],
                'address' => $blueprint['customer_address'] ?? null,
                'notes' => $blueprint['notes'],
                'status' => 'active',
                'company_id' => $this->company->id,
            ]);
            $customer->save();

            $customer->forceFill([
                'created_at' => $lead->created_at?->copy()->addDays(7) ?? now()->subMonths((int) $blueprint['months_ago']),
                'updated_at' => now()->subDays(abs(crc32($blueprint['key'])) % 10),
            ])->saveQuietly();

            $this->customers[$blueprint['key']] = $customer->fresh();
        }
    }

    private function upsertLeadActivities(): void
    {
        $scripts = [
            'new' => [
                ['type' => 'note', 'summary' => 'Lead captured from inbound interest form.', 'days_ago' => 2],
                ['type' => 'email', 'summary' => 'Sent welcome email with product overview.', 'days_ago' => 1],
            ],
            'contacted' => [
                ['type' => 'call', 'summary' => 'Introductory call completed; pain points captured.', 'days_ago' => 5],
                ['type' => 'email', 'summary' => 'Shared case study and scheduling link.', 'days_ago' => 3],
                ['type' => 'note', 'summary' => 'Decision maker confirmed for next meeting.', 'days_ago' => 1],
            ],
            'qualified' => [
                ['type' => 'meeting', 'summary' => 'Discovery meeting held with sales stakeholders.', 'days_ago' => 10],
                ['type' => 'call', 'summary' => 'Budget and timeline confirmed as qualified.', 'days_ago' => 6],
                ['type' => 'whatsapp', 'summary' => 'Quick follow-up on demo availability.', 'days_ago' => 2],
            ],
            'proposal_sent' => [
                ['type' => 'meeting', 'summary' => 'Live product demo delivered to buying team.', 'days_ago' => 12],
                ['type' => 'email', 'summary' => 'Proposal and pricing PDF sent for review.', 'days_ago' => 4],
                ['type' => 'call', 'summary' => 'Walked through proposal line items.', 'days_ago' => 1],
            ],
            'won' => [
                ['type' => 'meeting', 'summary' => 'Final stakeholder demo completed.', 'days_ago' => 20],
                ['type' => 'email', 'summary' => 'Contract and onboarding checklist sent.', 'days_ago' => 14],
                ['type' => 'note', 'summary' => 'Customer converted; kicked off onboarding.', 'days_ago' => 10],
                ['type' => 'call', 'summary' => 'Post-sale check-in — adoption going well.', 'days_ago' => 3],
            ],
            'lost' => [
                ['type' => 'call', 'summary' => 'Discussed requirements and competitive landscape.', 'days_ago' => 30],
                ['type' => 'email', 'summary' => 'Sent comparison sheet and pricing.', 'days_ago' => 22],
                ['type' => 'note', 'summary' => 'Marked lost — budget deferred; nurture later.', 'days_ago' => 18],
            ],
        ];

        foreach ($this->leadsBlueprint as $blueprint) {
            $lead = $this->leads[$blueprint['key']];
            $user = $this->users[$blueprint['assignee']];
            $entries = $scripts[$blueprint['status']] ?? $scripts['new'];

            foreach ($entries as $entry) {
                $occurredAt = now()->subDays((int) $entry['days_ago'])->setTime(11, 30);

                $activity = LeadActivity::withoutGlobalScope(CompanyScope::class)->firstOrNew([
                    'company_id' => $this->company->id,
                    'lead_id' => $lead->id,
                    'type' => $entry['type'],
                    'summary' => $entry['summary'],
                ]);

                $activity->fill([
                    'user_id' => $user->id,
                    'occurred_at' => $occurredAt,
                    'next_follow_up_date' => in_array($blueprint['status'], ['won', 'lost'], true)
                        ? null
                        : now()->addDays(3)->toDateString(),
                    'company_id' => $this->company->id,
                ]);
                $activity->save();
            }
        }
    }

    private function upsertTasks(): void
    {
        $taskBlueprints = [
            [
                'title' => 'Follow up with BrightPath Consulting',
                'description' => 'Confirm discovery call agenda and attendees.',
                'status' => 'pending',
                'priority' => 'high',
                'due_offset_days' => 1,
                'assignee' => 'sales',
                'lead' => 'brightpath',
            ],
            [
                'title' => 'Schedule product demo with Vertex Retail',
                'description' => 'Offer two demo slots next week for their ops lead.',
                'status' => 'pending',
                'priority' => 'medium',
                'due_offset_days' => 3,
                'assignee' => 'sales',
                'lead' => 'vertex',
            ],
            [
                'title' => 'Send proposal revision to BluePeak Logistics',
                'description' => 'Include annual discount and onboarding timeline.',
                'status' => 'in_progress',
                'priority' => 'high',
                'due_offset_days' => 0,
                'assignee' => 'manager',
                'lead' => 'bluepeak',
            ],
            [
                'title' => 'Follow up on NovaTech pricing discussion',
                'description' => 'Answer remaining questions on seat licensing.',
                'status' => 'pending',
                'priority' => 'urgent',
                'due_offset_days' => -1,
                'assignee' => 'manager',
                'lead' => 'novatech',
            ],
            [
                'title' => 'Review newly qualified UrbanEdge opportunity',
                'description' => 'Validate ICP fit before executive demo.',
                'status' => 'pending',
                'priority' => 'medium',
                'due_offset_days' => 2,
                'assignee' => 'manager',
                'lead' => 'urbanedge',
            ],
            [
                'title' => 'Call Summit Digital stakeholders',
                'description' => 'Re-engage after intro call; confirm blockers.',
                'status' => 'pending',
                'priority' => 'high',
                'due_offset_days' => -2,
                'assignee' => 'sales',
                'lead' => 'summit',
            ],
            [
                'title' => 'Prepare onboarding for ClearView Systems',
                'description' => 'Share kickoff checklist and admin invite.',
                'status' => 'in_progress',
                'priority' => 'high',
                'due_offset_days' => 1,
                'assignee' => 'manager',
                'customer' => 'clearview',
            ],
            [
                'title' => 'Quarterly check-in with Greenline Distribution',
                'description' => 'Review adoption metrics and expansion interest.',
                'status' => 'pending',
                'priority' => 'medium',
                'due_offset_days' => 7,
                'assignee' => 'sales',
                'customer' => 'greenline',
            ],
            [
                'title' => 'Customer success note for Harbor & Field',
                'description' => 'Document workflow wins for case study draft.',
                'status' => 'completed',
                'priority' => 'low',
                'due_offset_days' => -5,
                'assignee' => 'manager',
                'customer' => 'harbor',
            ],
            [
                'title' => 'Training session for Skylight Media team',
                'description' => 'Walk through lead stages and task board.',
                'status' => 'completed',
                'priority' => 'medium',
                'due_offset_days' => -10,
                'assignee' => 'sales',
                'customer' => 'skylight',
            ],
            [
                'title' => 'Expand seats discussion — Ironclad Facilities',
                'description' => 'Propose additional sales seats after growth.',
                'status' => 'pending',
                'priority' => 'medium',
                'due_offset_days' => 5,
                'assignee' => 'admin',
                'customer' => 'ironclad',
            ],
            [
                'title' => 'Send Willow Creek Partners renewal reminder',
                'description' => 'Annual renewal window opens next month.',
                'status' => 'pending',
                'priority' => 'low',
                'due_offset_days' => 14,
                'assignee' => 'manager',
                'customer' => 'willow',
            ],
            [
                'title' => 'Pulse Analytics — map reporting requirements',
                'description' => 'Capture preferred KPI views for leadership.',
                'status' => 'in_progress',
                'priority' => 'high',
                'due_offset_days' => 2,
                'assignee' => 'admin',
                'customer' => 'pulse',
            ],
            [
                'title' => 'Nurture email sequence for PrimeWorks',
                'description' => 'Add to nurture list after lost-reason review.',
                'status' => 'completed',
                'priority' => 'low',
                'due_offset_days' => -8,
                'assignee' => 'sales',
                'lead' => 'primeworks',
            ],
            [
                'title' => 'Cedar & Co. — qualify company size',
                'description' => 'Confirm number of sales users before demo.',
                'status' => 'pending',
                'priority' => 'medium',
                'due_offset_days' => 4,
                'assignee' => 'sales',
                'lead' => 'cedar',
            ],
            [
                'title' => 'Lumen Studio proposal follow-up',
                'description' => 'Confirm receipt and book decision call.',
                'status' => 'pending',
                'priority' => 'high',
                'due_offset_days' => -1,
                'assignee' => 'sales',
                'lead' => 'lumen',
            ],
            [
                'title' => 'Ridgeway Health Group — security questionnaire',
                'description' => 'Complete vendor security form for IT review.',
                'status' => 'completed',
                'priority' => 'high',
                'due_offset_days' => -12,
                'assignee' => 'manager',
                'customer' => 'ridgeway',
            ],
            [
                'title' => 'Weekly pipeline review',
                'description' => 'Review open stages with sales team.',
                'status' => 'completed',
                'priority' => 'medium',
                'due_offset_days' => -3,
                'assignee' => 'manager',
            ],
            [
                'title' => 'Prepare Q3 pipeline recap for leadership',
                'description' => 'Summarize open deals, conversion, and next-week follow-ups.',
                'status' => 'in_progress',
                'priority' => 'low',
                'due_offset_days' => 6,
                'assignee' => 'admin',
            ],
        ];

        Task::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('title', 'Prepare demo environment screenshots')
            ->update([
                'title' => 'Prepare Q3 pipeline recap for leadership',
                'description' => 'Summarize open deals, conversion, and next-week follow-ups.',
            ]);

        $sort = 0;
        foreach ($taskBlueprints as $blueprint) {
            $sort++;
            $assignee = $this->users[$blueprint['assignee']];
            $due = now()->startOfDay()->addDays((int) $blueprint['due_offset_days'])->setTime(17, 0);
            $completedAt = $blueprint['status'] === 'completed'
                ? $due->copy()->subHours(4)
                : null;

            $leadId = isset($blueprint['lead']) ? ($this->leads[$blueprint['lead']]->id ?? null) : null;
            $customerId = isset($blueprint['customer']) ? ($this->customers[$blueprint['customer']]->id ?? null) : null;

            $task = Task::withoutGlobalScope(CompanyScope::class)->firstOrNew([
                'company_id' => $this->company->id,
                'title' => $blueprint['title'],
            ]);

            $task->fill([
                'created_by' => $assignee->id,
                'assigned_to' => $assignee->id,
                'customer_id' => $customerId,
                'lead_id' => $leadId,
                'description' => $blueprint['description'],
                'priority' => $blueprint['priority'],
                'status' => $blueprint['status'],
                'sort_order' => $sort,
                'due_date' => $due,
                'completed_at' => $completedAt,
                'company_id' => $this->company->id,
            ]);
            $task->save();
        }
    }

    private function upsertActivityLogs(): void
    {
        $demoIp = '203.0.113.24';
        $rows = [
            [
                'key' => 'lead-created-brightpath',
                'actor' => 'sales',
                'action' => 'lead.created',
                'subject' => $this->leads['brightpath'] ?? null,
                'hours_ago' => 76,
                'properties' => ['name' => 'Elena Vasquez'],
            ],
            [
                'key' => 'lead-status-vertex',
                'actor' => 'sales',
                'action' => 'lead.status_changed',
                'subject' => $this->leads['vertex'] ?? null,
                'hours_ago' => 54,
                'properties' => ['from' => 'new', 'to' => 'contacted'],
            ],
            [
                'key' => 'task-created-summit',
                'actor' => 'manager',
                'action' => 'task.created',
                'subject' => Task::withoutGlobalScope(CompanyScope::class)
                    ->where('company_id', $this->company->id)
                    ->where('title', 'Call Summit Digital stakeholders')
                    ->first(),
                'hours_ago' => 40,
                'properties' => ['title' => 'Call Summit Digital stakeholders'],
            ],
            [
                'key' => 'lead-converted-amelia',
                'actor' => 'manager',
                'action' => 'lead.converted',
                'subject' => $this->leads['clearview'] ?? $this->leads['amelia'] ?? null,
                'hours_ago' => 28,
                'properties' => ['name' => 'Amelia Grant'],
            ],
            [
                'key' => 'customer-updated-pulse',
                'actor' => 'admin',
                'action' => 'customer.updated',
                'subject' => $this->customers['pulse'] ?? null,
                'hours_ago' => 18,
                'properties' => ['name' => 'Aaron Kim'],
            ],
            [
                'key' => 'lead-assigned-priya',
                'actor' => 'manager',
                'action' => 'lead.assigned',
                'subject' => $this->leads['bluepeak'] ?? null,
                'hours_ago' => 12,
                'properties' => ['name' => 'Priya Nair', 'to' => 'Jordan Hale'],
            ],
            [
                'key' => 'user-login-sales',
                'actor' => 'sales',
                'action' => 'user.login',
                'subject' => $this->users['sales'],
                'hours_ago' => 6,
                'properties' => [],
            ],
            [
                'key' => 'task-updated-pipeline',
                'actor' => 'admin',
                'action' => 'task.updated',
                'subject' => Task::withoutGlobalScope(CompanyScope::class)
                    ->where('company_id', $this->company->id)
                    ->where('title', 'Prepare Q3 pipeline recap for leadership')
                    ->first(),
                'hours_ago' => 3,
                'properties' => ['title' => 'Prepare Q3 pipeline recap for leadership'],
            ],
        ];

        foreach ($rows as $row) {
            if ($row['subject'] === null) {
                continue;
            }

            $existing = ActivityLog::withoutGlobalScope(CompanyScope::class)
                ->where('company_id', $this->company->id)
                ->get()
                ->first(fn (ActivityLog $log) => ($log->properties['demo_key'] ?? null) === $row['key']);

            $log = $existing ?? new ActivityLog;
            $log->fill([
                'user_id' => $this->users[$row['actor']]->id,
                'action' => $row['action'],
                'subject_type' => $row['subject']::class,
                'subject_id' => $row['subject']->getKey(),
                'properties' => array_merge($row['properties'], ['demo_key' => $row['key']]),
                'ip_address' => $demoIp,
            ]);
            $log->company_id = $this->company->id;
            $log->save();
            $log->forceFill([
                'created_at' => now()->subHours($row['hours_ago']),
                'updated_at' => now()->subHours($row['hours_ago']),
            ])->save();
        }
    }
}
