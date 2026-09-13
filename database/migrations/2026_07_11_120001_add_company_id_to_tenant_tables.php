<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tenant-owned tables that receive company_id in Phase 1A.
     *
     * @var list<string>
     */
    private array $tenantTables = [
        'users',
        'leads',
        'customers',
        'tasks',
        'activity_logs',
        'lead_activities',
        'roles',
    ];

    public function up(): void
    {
        // 1) Add nullable company_id without a foreign key so backfill stays simple.
        foreach ($this->tenantTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
                $table->index('company_id');
            });
        }

        // 2) Create the default company and attach every existing row to it.
        $now = now();

        $companyId = DB::table('companies')->insertGetId([
            'name' => 'Default Company',
            'slug' => 'default',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($this->tenantTables as $tableName) {
            DB::table($tableName)
                ->whereNull('company_id')
                ->update(['company_id' => $companyId]);
        }

        // 3) Tighten nullability and keep CRM inserts working until Phase 1B+:
        //    - users.company_id stays nullable (future Super Admin)
        //    - other tenant tables become NOT NULL
        //    - DB default = default company so existing create paths still work
        foreach ($this->tenantTables as $tableName) {
            $nullable = $tableName === 'users';

            Schema::table($tableName, function (Blueprint $table) use ($nullable, $companyId) {
                $table->unsignedBigInteger('company_id')
                    ->nullable($nullable)
                    ->default($companyId)
                    ->change();
            });
        }

        // 4) Add foreign keys (restrict delete — company removal must be intentional).
        foreach ($this->tenantTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->restrictOnDelete();
            });
        }

        // 5) Unique constraints become per-company.
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['company_id', 'email']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['company_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'slug']);
            $table->unique('slug');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'email']);
            $table->unique('email');
        });

        foreach ($this->tenantTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['company_id']);
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['company_id']);
                $table->dropColumn('company_id');
            });
        }

        DB::table('companies')->where('slug', 'default')->delete();
    }
};
