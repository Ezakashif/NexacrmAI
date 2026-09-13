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
        Schema::create('leads', function (Blueprint $table) {
           $table->id();

        $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
        $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

        $table->string('name');
        $table->string('email')->nullable();
        $table->string('phone')->nullable();

        $table->string('company')->nullable();

        $table->string('source')->nullable(); 
        // facebook, website, referral, whatsapp, etc.

        $table->string('status')->default('new');
        /*
            new
            contacted
            qualified
            proposal_sent
            won
            lost
        */

        $table->decimal('estimated_value', 10, 2)->nullable();

        $table->text('notes')->nullable();

        $table->date('follow_up_date')->nullable();

        $table->timestamps();

        $table->index(['status', 'assigned_to']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
