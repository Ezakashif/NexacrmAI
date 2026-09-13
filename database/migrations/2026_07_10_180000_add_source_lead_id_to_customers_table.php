<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('source_lead_id')
                ->nullable()
                ->after('created_by')
                ->constrained('leads')
                ->nullOnDelete();
        });

        $this->backfillSourceLeads();
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_lead_id');
        });
    }

    /**
     * Best-effort link for existing conversions: unmatched won leads with the same email.
     * Uses the query builder so later SoftDeletes model scopes cannot break migrate:fresh.
     */
    protected function backfillSourceLeads(): void
    {
        $usedLeadIds = DB::table('customers')
            ->whereNotNull('source_lead_id')
            ->pluck('source_lead_id')
            ->all();

        $customers = DB::table('customers')
            ->whereNull('source_lead_id')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('id')
            ->get(['id', 'email']);

        foreach ($customers as $customer) {
            $email = strtolower(trim((string) $customer->email));

            if ($email === '') {
                continue;
            }

            $leadQuery = DB::table('leads')
                ->where('status', 'won')
                ->whereNotNull('email')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->orderBy('updated_at')
                ->orderBy('id');

            if ($usedLeadIds !== []) {
                $leadQuery->whereNotIn('id', $usedLeadIds);
            }

            $lead = $leadQuery->first();

            if (! $lead) {
                continue;
            }

            DB::table('customers')
                ->where('id', $customer->id)
                ->update(['source_lead_id' => $lead->id]);

            $usedLeadIds[] = $lead->id;
        }
    }
};
