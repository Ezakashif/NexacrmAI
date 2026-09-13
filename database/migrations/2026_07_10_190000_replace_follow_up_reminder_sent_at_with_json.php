<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->json('follow_up_reminders_sent')->nullable()->after('follow_up_date');
        });

        if (Schema::hasColumn('leads', 'follow_up_reminder_sent_at')) {
            DB::table('leads')
                ->whereNotNull('follow_up_reminder_sent_at')
                ->orderBy('id')
                ->chunkById(100, function ($leads) {
                    foreach ($leads as $lead) {
                        DB::table('leads')->where('id', $lead->id)->update([
                            'follow_up_reminders_sent' => json_encode([
                                'due' => $lead->follow_up_reminder_sent_at,
                            ]),
                        ]);
                    }
                });

            Schema::table('leads', function (Blueprint $table) {
                $table->dropColumn('follow_up_reminder_sent_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('follow_up_reminder_sent_at')->nullable()->after('follow_up_date');
        });

        DB::table('leads')
            ->whereNotNull('follow_up_reminders_sent')
            ->orderBy('id')
            ->chunkById(100, function ($leads) {
                foreach ($leads as $lead) {
                    $sent = json_decode($lead->follow_up_reminders_sent, true) ?: [];
                    $dueAt = $sent['due'] ?? null;

                    DB::table('leads')->where('id', $lead->id)->update([
                        'follow_up_reminder_sent_at' => $dueAt,
                    ]);
                }
            });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('follow_up_reminders_sent');
        });
    }
};
