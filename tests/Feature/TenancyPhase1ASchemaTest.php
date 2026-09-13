<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenancyPhase1ASchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_company_exists_after_migrations(): void
    {
        $company = DB::table('companies')->where('slug', 'default')->first();

        $this->assertNotNull($company);
        $this->assertSame('Default Company', $company->name);
        $this->assertSame('active', $company->status);
    }

    public function test_tenant_tables_have_company_id_column(): void
    {
        foreach ([
            'users',
            'leads',
            'customers',
            'tasks',
            'activity_logs',
            'lead_activities',
            'roles',
        ] as $table) {
            $this->assertTrue(
                Schema::hasColumn($table, 'company_id'),
                "Expected {$table}.company_id to exist."
            );
        }

        $this->assertFalse(Schema::hasColumn('permissions', 'company_id'));
    }

    public function test_existing_rows_are_backfilled_to_default_company(): void
    {
        $companyId = (int) DB::table('companies')->where('slug', 'default')->value('id');

        $userId = DB::table('users')->insertGetId([
            'name' => 'Backfill User',
            'email' => 'backfill-user@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'status' => 'active',
            'company_id' => $companyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $leadId = DB::table('leads')->insertGetId([
            'name' => 'Backfill Lead',
            'email' => 'backfill-lead@example.com',
            'status' => 'new',
            'source' => 'website',
            'created_by' => $userId,
            'company_id' => $companyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame($companyId, (int) DB::table('users')->where('id', $userId)->value('company_id'));
        $this->assertSame($companyId, (int) DB::table('leads')->where('id', $leadId)->value('company_id'));
    }

    public function test_new_lead_without_company_id_uses_database_default(): void
    {
        $companyId = (int) DB::table('companies')->where('slug', 'default')->value('id');

        $userId = DB::table('users')->insertGetId([
            'name' => 'Defaulting User',
            'email' => 'defaulting-user@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $leadId = DB::table('leads')->insertGetId([
            'name' => 'Defaulting Lead',
            'email' => 'defaulting-lead@example.com',
            'status' => 'new',
            'source' => 'website',
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame($companyId, (int) DB::table('users')->where('id', $userId)->value('company_id'));
        $this->assertSame($companyId, (int) DB::table('leads')->where('id', $leadId)->value('company_id'));
    }

    public function test_users_company_id_can_be_null_for_future_super_admin(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Platform User',
            'email' => 'platform@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
            'company_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNull(DB::table('users')->where('id', $userId)->value('company_id'));
    }
}
