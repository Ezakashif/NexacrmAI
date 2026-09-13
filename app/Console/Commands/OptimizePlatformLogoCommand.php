<?php

namespace App\Console\Commands;

use App\Services\SuperAdmin\PlatformLogoProcessor;
use App\Services\SuperAdmin\PlatformSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizePlatformLogoCommand extends Command
{
    protected $signature = 'platform:optimize-logo
        {--packaged=branding/algos-logo.png : Packaged public asset to install when no logo exists}
        {--force-packaged : Replace the current logo with the packaged transparent asset}';

    protected $description = 'Make the platform logo transparent, trim empty padding, and normalize size';

    public function handle(PlatformLogoProcessor $processor, PlatformSettingsService $settings): int
    {
        $current = $settings->get('platform_logo_path');

        if ($this->option('force-packaged') || blank($current) || ! Storage::disk('public')->exists((string) $current)) {
            $path = $processor->storePackagedAsset((string) $this->option('packaged'));
            $lightPath = null;

            if (is_file(public_path('branding/algos-logo-light.png'))) {
                $lightPath = $processor->storePackagedAsset('branding/algos-logo-light.png');
            }

            $settings->setMany(array_filter([
                'platform_logo_path' => $path,
                'platform_logo_light_path' => $lightPath,
            ]));
            $settings->applyBranding();
            $this->info("Installed packaged logo at [{$path}].");

            return self::SUCCESS;
        }

        $absolute = Storage::disk('public')->path((string) $current);
        $tempOutput = sys_get_temp_dir().'/platform_logo_optimized.png';

        $processor->processToTransparentPng($absolute, $tempOutput);

        $optimizedRelative = 'platform/'.basename((string) $current, '.'.pathinfo((string) $current, PATHINFO_EXTENSION)).'-optimized.png';
        Storage::disk('public')->put($optimizedRelative, file_get_contents($tempOutput));
        @unlink($tempOutput);

        if ($current !== $optimizedRelative && Storage::disk('public')->exists((string) $current)) {
            Storage::disk('public')->delete((string) $current);
        }

        $settings->setMany(['platform_logo_path' => $optimizedRelative]);
        $settings->applyBranding();

        $this->info("Optimized platform logo saved to [{$optimizedRelative}].");

        return self::SUCCESS;
    }
}
