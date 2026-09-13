<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_visits', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable()->index();
            $table->string('name', 120)->nullable();
            $table->string('company', 160)->nullable();
            $table->string('persona', 40)->index();
            $table->boolean('contact_consent')->default(false);
            $table->boolean('is_anonymous')->default(false)->index();
            $table->string('status', 32)->default('new')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_visits');
    }
};
