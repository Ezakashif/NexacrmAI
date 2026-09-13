<?php

use App\Services\SuperAdmin\CompanySoftDeleteService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Soft-deleted companies previously kept user emails / slugs reserved.
        // Release those identifiers so new tenants can reuse them.
        app(CompanySoftDeleteService::class)->releaseIdentifiersForTrashedCompanies();
    }

    public function down(): void
    {
        // Irreversible data repair — intentionally blank.
    }
};
