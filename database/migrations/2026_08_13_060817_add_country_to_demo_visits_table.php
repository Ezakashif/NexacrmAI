<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demo_visits', function (Blueprint $table) {
            $table->string('country', 2)->nullable()->after('company')->index();
        });
    }

    public function down(): void
    {
        Schema::table('demo_visits', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
