<?php

namespace App\Console\Commands;

use App\Services\Demo\DemoResetService;
use App\Support\DemoEnvironment;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

class ResetDemoEnvironmentCommand extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Reset ONLY the Northstar Solutions live-demo tenant to the seeded dataset.';

    public function handle(DemoResetService $reset): int
    {
        $this->warn('This command resets only the verified demo tenant ('.DemoEnvironment::companySlug().').');

        try {
            $company = $reset->reset();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (Throwable $e) {
            report($e);
            $this->error('Demo reset failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Demo tenant reset: '.$company->name.' ('.$company->slug.').');

        return self::SUCCESS;
    }
}
