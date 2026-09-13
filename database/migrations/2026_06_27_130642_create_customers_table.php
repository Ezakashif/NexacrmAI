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
        Schema::create('customers', function (Blueprint $table) {
        $table->id();

        $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

        $table->string('name');
        $table->string('email')->nullable();
        $table->string('phone')->nullable();

        $table->text('address')->nullable();
        $table->text('notes')->nullable();

        $table->string('company_name')->nullable();
        $table->string('status')->default('active');

        $table->timestamps();

        $table->index(['name', 'email', 'phone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
