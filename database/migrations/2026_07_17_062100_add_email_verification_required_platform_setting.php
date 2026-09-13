<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        $exists = DB::table('platform_settings')
            ->where('key', 'email_verification_required')
            ->exists();

        if ($exists) {
            return;
        }

        $now = now();

        DB::table('platform_settings')->insert([
            'key' => 'email_verification_required',
            'value' => '1',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        DB::table('platform_settings')
            ->where('key', 'email_verification_required')
            ->delete();
    }
};
