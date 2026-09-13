<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
             $table->id();

        $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
        $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

        $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();

        $table->string('title');
        $table->text('description')->nullable();

        $table->string('priority')->default('medium');
        /*
            low
            medium
            high
            urgent
        */

        $table->string('status')->default('pending');
        /*
            pending
            in_progress
            completed
            cancelled
        */

        $table->dateTime('due_date')->nullable();
        $table->dateTime('completed_at')->nullable();

        $table->timestamps();

        $table->index(['assigned_to', 'status']);
    });
    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
