<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use RuntimeException;

/** Records the last time Laravel's scheduler actually dispatched its heartbeat event. */
class SchedulerHeartbeat
{
    public function path(): string
    {
        return storage_path('framework/schedule-heartbeat');
    }

    public function record(): void
    {
        $path = $this->path();
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create the scheduler heartbeat directory.');
        }

        if (file_put_contents($path, now()->toIso8601String(), LOCK_EX) === false) {
            throw new RuntimeException('Unable to write the scheduler heartbeat.');
        }
    }

    public function lastRun(): ?Carbon
    {
        $value = @file_get_contents($this->path());
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse(trim($value));
        } catch (\Throwable) {
            return null;
        }
    }
}
