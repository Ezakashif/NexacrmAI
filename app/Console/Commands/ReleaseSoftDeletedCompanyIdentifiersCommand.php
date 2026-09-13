<?php

namespace App\Console\Commands;

use App\Services\SuperAdmin\CompanySoftDeleteService;
use Illuminate\Console\Command;

class ReleaseSoftDeletedCompanyIdentifiersCommand extends Command
{
    protected $signature = 'companies:release-deleted-identifiers';

    protected $description = 'Free emails and slugs still held by soft-deleted companies so they can be reused';

    public function handle(CompanySoftDeleteService $softDeletes): int
    {
        $count = $softDeletes->releaseIdentifiersForTrashedCompanies();

        if ($count === 0) {
            $this->info('No soft-deleted company identifiers needed repair.');
        } else {
            $this->info("Released identifiers for {$count} soft-deleted ".str('company')->plural($count).'.');
        }

        return self::SUCCESS;
    }
}
