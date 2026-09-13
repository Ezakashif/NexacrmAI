<?php

namespace App\Services\SuperAdmin;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SystemHealthService
{
    /**
     * @return array<string, array{status: string, label: string, detail: string}>
     */
    public function snapshot(): array
    {
        return [
            'database' => $this->databaseHealth(),
            'queue' => $this->queueHealth(),
            'scheduler' => $this->schedulerHealth(),
            'failed_jobs' => $this->failedJobsHealth(),
            'storage' => $this->storageHealth(),
        ];
    }

    /**
     * @return array{status: string, label: string, detail: string}
     */
    private function databaseHealth(): array
    {
        try {
            DB::select('select 1');

            return [
                'status' => 'ok',
                'label' => 'Database',
                'detail' => 'Connection healthy',
            ];
        } catch (\Throwable) {
            return [
                'status' => 'error',
                'label' => 'Database',
                'detail' => 'Unable to reach the database',
            ];
        }
    }

    /**
     * @return array{status: string, label: string, detail: string}
     */
    private function queueHealth(): array
    {
        if (! Schema::hasTable('jobs')) {
            return [
                'status' => 'unknown',
                'label' => 'Queue',
                'detail' => 'Jobs table not available',
            ];
        }

        $pending = (int) DB::table('jobs')->count();

        return [
            'status' => $pending > 100 ? 'warning' : 'ok',
            'label' => 'Queue',
            'detail' => $pending === 0
                ? 'No pending jobs'
                : "{$pending} pending ".str('job')->plural($pending),
        ];
    }

    /**
     * @return array{status: string, label: string, detail: string}
     */
    private function schedulerHealth(): array
    {
        $lastRun = PlatformSetting::query()->where('key', 'scheduler_last_run_at')->value('value');

        if (! filled($lastRun)) {
            return [
                'status' => 'unknown',
                'label' => 'Scheduler',
                'detail' => 'No heartbeat recorded yet',
            ];
        }

        $lastRunAt = \Illuminate\Support\Carbon::parse($lastRun);
        $stale = $lastRunAt->lt(now()->subMinutes(15));

        return [
            'status' => $stale ? 'warning' : 'ok',
            'label' => 'Scheduler',
            'detail' => 'Last heartbeat '.$lastRunAt->diffForHumans(),
        ];
    }

    /**
     * @return array{status: string, label: string, detail: string}
     */
    private function failedJobsHealth(): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [
                'status' => 'unknown',
                'label' => 'Failed jobs',
                'detail' => 'Failed jobs table not available',
            ];
        }

        $count = (int) DB::table('failed_jobs')->count();

        return [
            'status' => $count > 0 ? 'warning' : 'ok',
            'label' => 'Failed jobs',
            'detail' => $count === 0
                ? 'None'
                : "{$count} failed ".str('job')->plural($count),
        ];
    }

    /**
     * @return array{status: string, label: string, detail: string}
     */
    private function storageHealth(): array
    {
        try {
            $disk = Storage::disk('public');
            $disk->exists('.');

            $bytes = $this->directorySize(storage_path('app/public'));
            $detail = $this->formatBytes($bytes).' used in application storage';

            return [
                'status' => 'ok',
                'label' => 'Storage',
                'detail' => $detail,
            ];
        } catch (\Throwable) {
            return [
                'status' => 'error',
                'label' => 'Storage',
                'detail' => 'Unable to inspect application storage',
            ];
        }
    }

    private function directorySize(string $path): int
    {
        if (! is_dir($path)) {
            return 0;
        }

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $value = (float) $bytes;

        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return round($value, 1).' '.$units[$i];
    }
}
