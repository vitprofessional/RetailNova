<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        // Reject cached configuration before providers can boot with live settings.
        if ($app->configurationIsCached()) {
            throw new \RuntimeException('Tests refuse cached Laravel configuration.');
        }
        $app->loadEnvironmentFrom('.env.testing');
        $app->make(Kernel::class)->bootstrap();

        if (!$app->environment('testing') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
            || !empty(config('database.connections.sqlite.url'))) {
            throw new \RuntimeException('Tests require SQLite :memory: with no DB_URL override.');
        }

        // Named connections and alternate transports must not reach live services.
        config([
            'database.connections' => ['sqlite' => config('database.connections.sqlite')],
            'cache.default' => 'array',
            'cache.stores' => ['array' => config('cache.stores.array')],
            'session.driver' => 'array',
            'mail.default' => 'array',
            'mail.mailers' => ['array' => ['transport' => 'array']],
            'queue.default' => 'sync',
            'queue.connections' => ['sync' => ['driver' => 'sync']],
            'services' => [],
            'audit.drivers.database.connection' => null,
        ]);
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        \Illuminate\Support\Facades\Mail::fake();
        \Illuminate\Support\Facades\Notification::fake();

        return $app;
    }
}
