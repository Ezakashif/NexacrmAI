<?php

namespace App\Services\SuperAdmin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlatformLogoProcessor
{
    /**
     * Store an uploaded logo as a trimmed transparent PNG on the public disk.
     */
    public function storeProcessed(UploadedFile $file, string $directory = 'platform'): string
    {
        $tempInput = tempnam(sys_get_temp_dir(), 'logo_in_');
        $tempOutput = sys_get_temp_dir().'/logo_out_'.Str::random(8).'.png';

        try {
            File::put($tempInput, File::get($file->getRealPath()));
            $this->processToTransparentPng($tempInput, $tempOutput);

            $filename = $directory.'/'.Str::uuid().'.png';
            Storage::disk('public')->put($filename, File::get($tempOutput));

            return $filename;
        } finally {
            @unlink($tempInput);
            @unlink($tempOutput);
        }
    }

    /**
     * Copy a packaged branding asset into public storage.
     */
    public function storePackagedAsset(string $publicRelativePath, string $directory = 'platform'): string
    {
        $source = public_path($publicRelativePath);

        if (! is_file($source)) {
            throw new \RuntimeException("Branding asset missing: {$publicRelativePath}");
        }

        $filename = $directory.'/'.Str::uuid().'.png';
        $tempOutput = sys_get_temp_dir().'/logo_pack_'.Str::random(8).'.png';

        try {
            $this->processToTransparentPng($source, $tempOutput);
            Storage::disk('public')->put($filename, File::get($tempOutput));

            return $filename;
        } finally {
            @unlink($tempOutput);
        }
    }

    public function processToTransparentPng(string $inputPath, string $outputPath): void
    {
        $script = base_path('scripts/process_platform_logo.py');

        $result = Process::run(['python3', $script, $inputPath, $outputPath]);

        if (! $result->successful() || ! is_file($outputPath)) {
            if (! @copy($inputPath, $outputPath)) {
                throw new \RuntimeException('Unable to process platform logo: '.$result->errorOutput());
            }
        }
    }
}
