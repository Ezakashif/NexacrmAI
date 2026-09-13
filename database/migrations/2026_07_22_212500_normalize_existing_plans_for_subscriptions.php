<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('short_description')->nullable()->after('slug');
            $table->text('description')->nullable()->after('short_description');
            $table->decimal('monthly_price', 12, 2)->default(0)->after('price_cents');
            $table->decimal('yearly_price', 12, 2)->default(0)->after('monthly_price');
            $table->string('currency', 3)->default('USD')->after('yearly_price');
            $table->string('billing_cycle', 20)->default('monthly')->after('currency');
            $table->unsignedSmallInteger('trial_days')->default(14)->after('billing_cycle');
            $table->boolean('is_free')->default(false)->after('trial_days');
            $table->boolean('is_featured')->default(false)->after('is_free');
            $table->boolean('is_public')->default(true)->after('is_featured');
            $table->unsignedInteger('sort_order')->default(0)->after('is_public');
            $table->text('notes')->nullable()->after('sort_order');
            $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->index(['is_active', 'is_public', 'sort_order']);
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('feature_key');
            $table->string('feature_name');
            $table->text('description')->nullable();
            $table->string('feature_type', 20)->default('boolean');
            $table->string('feature_value')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_highlighted')->default(false);
            $table->timestamps();

            $table->unique(['plan_id', 'feature_key']);
            $table->index(['plan_id', 'sort_order']);
        });

        Schema::create('plan_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('limit_key');
            $table->string('limit_name');
            $table->string('limit_value')->nullable();
            $table->string('unit', 40)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['plan_id', 'limit_key']);
            $table->index(['plan_id', 'sort_order']);
        });

        DB::transaction(function (): void {
            $plans = DB::table('plans')->select([
                'id', 'price_cents', 'max_users', 'max_leads', 'max_customers',
            ])->get();

            foreach ($plans as $position => $plan) {
                $price = ((int) $plan->price_cents) / 100;

                DB::table('plans')->where('id', $plan->id)->update([
                    'monthly_price' => $price,
                    'yearly_price' => $price,
                    'is_free' => $price === 0,
                    'sort_order' => $position + 1,
                ]);

                foreach ([
                    'users' => ['Users', $plan->max_users],
                    'leads' => ['Leads', $plan->max_leads],
                    'customers' => ['Customers', $plan->max_customers],
                ] as $key => [$name, $value]) {
                    DB::table('plan_limits')->insert([
                        'plan_id' => $plan->id,
                        'limit_key' => $key,
                        'limit_name' => $name,
                        'limit_value' => $value === null ? null : (string) $value,
                        'unit' => 'count',
                        'description' => 'Migrated from the legacy plan limit.',
                        'sort_order' => match ($key) {
                            'users' => 1,
                            'leads' => 2,
                            default => 3,
                        },
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_limits');
        Schema::dropIfExists('plan_features');

        Schema::table('plans', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'is_public', 'sort_order']);
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropSoftDeletes();
            $table->dropColumn([
                'short_description',
                'description',
                'monthly_price',
                'yearly_price',
                'currency',
                'billing_cycle',
                'trial_days',
                'is_free',
                'is_featured',
                'is_public',
                'sort_order',
                'notes',
            ]);
        });
    }
};
