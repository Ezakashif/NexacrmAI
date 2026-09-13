<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('max_users')->nullable();
            $table->unsignedInteger('max_leads')->nullable();
            $table->unsignedInteger('max_customers')->nullable();
            $table->unsignedInteger('price_cents')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('impersonation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('super_admin_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('target_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'started_at']);
            $table->index(['super_admin_id', 'started_at']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('email')->nullable()->after('slug');
            $table->string('phone', 50)->nullable()->after('email');
            $table->string('logo_path')->nullable()->after('phone');
            $table->foreignId('owner_id')->nullable()->after('logo_path')->constrained('users')->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->after('owner_id')->constrained('plans')->nullOnDelete();
            $table->string('subscription_status', 20)->default('trial')->after('status');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            $table->timestamp('last_active_at')->nullable()->after('trial_ends_at');
            $table->softDeletes();

            $table->index('subscription_status');
            $table->index('last_active_at');
            $table->index('trial_ends_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
        });

        $now = now();

        DB::table('plans')->insert([
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'max_users' => 5,
                'max_leads' => 500,
                'max_customers' => 200,
                'price_cents' => 0,
                'is_default' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'max_users' => 25,
                'max_leads' => 5000,
                'max_customers' => 2000,
                'price_cents' => 4900,
                'is_default' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'max_users' => null,
                'max_leads' => null,
                'max_customers' => null,
                'price_cents' => 14900,
                'is_default' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $defaultPlanId = DB::table('plans')->where('slug', 'starter')->value('id');
        $trialDays = 14;

        DB::table('companies')->whereNull('plan_id')->update([
            'plan_id' => $defaultPlanId,
            'subscription_status' => 'active',
            'trial_ends_at' => null,
        ]);

        DB::table('platform_settings')->insert([
            ['key' => 'platform_name', 'value' => config('app.name', 'CRM'), 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'platform_logo_path', 'value' => null, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'default_timezone', 'value' => config('app.timezone', 'UTC'), 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'default_currency', 'value' => 'USD', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'registration_enabled', 'value' => '0', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'maintenance_mode', 'value' => '0', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'trial_duration_days', 'value' => (string) $trialDays, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'mail_from_name', 'value' => config('mail.from.name'), 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'mail_from_address', 'value' => config('mail.from.address'), 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'broadcast_announcement', 'value' => null, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'default_company_status', 'value' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'scheduler_last_run_at', 'value' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_login_at');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn([
                'email',
                'phone',
                'logo_path',
                'subscription_status',
                'trial_ends_at',
                'last_active_at',
                'deleted_at',
            ]);
        });

        Schema::dropIfExists('impersonation_logs');
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('plans');
    }
};
