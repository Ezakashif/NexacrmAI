<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_alert_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('alert_type');
            $table->string('fingerprint', 64);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'alert_type'], 'platform_alert_deliveries_user_alert_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_alert_deliveries');
    }
};
