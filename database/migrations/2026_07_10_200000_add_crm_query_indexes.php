<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes for common CRM filters and dashboard/report queries.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->index('follow_up_date');
            $table->index('created_at');
            $table->index('source');
            $table->index('email');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
            $table->index('email');
            $table->unique('source_lead_id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index('due_date');
            $table->index('created_at');
            $table->index('customer_id');
            $table->index('lead_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['subject_type', 'subject_id', 'action'], 'activity_logs_subject_action_index');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['follow_up_date']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['source']);
            $table->dropIndex(['email']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['source_lead_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['email']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['due_date']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['lead_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_subject_action_index');
        });
    }
};
