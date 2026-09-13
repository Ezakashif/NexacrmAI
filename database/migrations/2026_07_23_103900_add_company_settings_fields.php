<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('address_line_1')->nullable()->after('phone');
            $table->string('address_line_2')->nullable()->after('address_line_1');
            $table->string('city')->nullable()->after('address_line_2');
            $table->string('state')->nullable()->after('city');
            $table->string('postal_code', 32)->nullable()->after('state');
            $table->string('country', 2)->nullable()->after('postal_code');
            $table->string('timezone')->nullable()->after('country');
            $table->string('currency', 3)->nullable()->after('timezone');
            $table->json('business_hours')->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['address_line_1', 'address_line_2', 'city', 'state', 'postal_code', 'country', 'timezone', 'currency', 'business_hours']);
        });
    }
};
