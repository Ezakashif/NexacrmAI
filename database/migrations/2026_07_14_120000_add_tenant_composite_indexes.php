<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->index(['company_id', 'status'], 'leads_company_id_status_index');
            $table->index(['company_id', 'assigned_to'], 'leads_company_id_assigned_to_index');
            $table->index(['company_id', 'follow_up_date'], 'leads_company_id_follow_up_date_index');
            $table->index(['company_id', 'created_at'], 'leads_company_id_created_at_index');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index(['company_id', 'status'], 'customers_company_id_status_index');
            $table->index(['company_id', 'created_at'], 'customers_company_id_created_at_index');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['company_id', 'status'], 'tasks_company_id_status_index');
            $table->index(['company_id', 'due_date'], 'tasks_company_id_due_date_index');
            $table->index(['company_id', 'assigned_to'], 'tasks_company_id_assigned_to_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['company_id', 'status'], 'users_company_id_status_index');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'activity_logs_company_id_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_company_id_status_index');
            $table->dropIndex('leads_company_id_assigned_to_index');
            $table->dropIndex('leads_company_id_follow_up_date_index');
            $table->dropIndex('leads_company_id_created_at_index');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_company_id_status_index');
            $table->dropIndex('customers_company_id_created_at_index');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_company_id_status_index');
            $table->dropIndex('tasks_company_id_due_date_index');
            $table->dropIndex('tasks_company_id_assigned_to_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_company_id_status_index');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_company_id_created_at_index');
        });
    }
};
