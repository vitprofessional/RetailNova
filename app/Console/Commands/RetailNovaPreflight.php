<?php

namespace App\Console\Commands;

use App\Support\SchedulerHeartbeat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RetailNovaPreflight extends Command
{
    protected $signature = 'retailnova:preflight';

    protected $description = 'Check server requirements before enabling RetailNova.';

    private bool $failed = false;

    public function handle(SchedulerHeartbeat $heartbeat): int
    {
        $this->check('PHP >= 8.2', version_compare(PHP_VERSION, '8.2.0', '>='), PHP_VERSION);

        foreach (['gd', 'exif', 'intl', 'mbstring', 'zip', 'pdo_mysql'] as $extension) {
            $this->check('PHP extension '.$extension, extension_loaded($extension));
        }

        $this->check('APP_DEBUG is disabled', config('app.debug') === false);
        $this->check('APP_KEY is configured', is_string(config('app.key')) && trim(config('app.key')) !== '');
        $appUrl = (string) config('app.url');
        $this->check('APP_URL uses HTTPS', strtolower((string) parse_url($appUrl, PHP_URL_SCHEME)) === 'https', $appUrl);

        foreach ([storage_path(), base_path('bootstrap/cache'), public_path('uploads')] as $path) {
            $this->check('Writable: '.$this->relativePath($path), is_dir($path) && is_writable($path));
        }

        $databaseReady = false;
        try {
            DB::connection()->getPdo();
            $databaseReady = true;
            $this->check('Database connection', true, DB::connection()->getName());
        } catch (Throwable $exception) {
            $this->check('Database connection', false, class_basename($exception));
        }

        if ($databaseReady) {
            try {
                $repository = app('migration.repository');
                if (!$repository->repositoryExists()) {
                    $this->check('Pending migrations', false, 'migration repository is missing');
                } else {
                    $ran = $repository->getRan();
                    $files = app('migrator')->getMigrationFiles(database_path('migrations'));
                    $pending = array_values(array_diff(array_keys($files), $ran));
                    $this->check('Pending migrations', $pending === [], $pending ? implode(', ', $pending) : 'none');
                }
            } catch (Throwable $exception) {
                $this->check('Pending migrations', false, class_basename($exception));
            }
        } else {
            $this->check('Pending migrations', false, 'database unavailable');
        }

        $lastRun = $heartbeat->lastRun();
        $fresh = $lastRun !== null && $lastRun->lte(now()->addMinute()) && $lastRun->gte(now()->subMinutes(3));
        $this->check(
            'Scheduler last ran',
            $fresh,
            $lastRun?->toDateTimeString() ?? 'no heartbeat recorded (cron should run every minute)'
        );

        return $this->failed ? self::FAILURE : self::SUCCESS;
    }

    private function check(string $label, bool $passed, ?string $detail = null): void
    {
        if (!$passed) {
            $this->failed = true;
        }

        $this->line(($passed ? '<fg=green>✓</>' : '<fg=red>✗</>').' '.$label.($detail !== null && $detail !== '' ? ': '.$detail : ''));
    }

    private function relativePath(string $path): string
    {
        $base = rtrim(base_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }
}
