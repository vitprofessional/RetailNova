<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\SuperAdmin;
use App\Http\Middleware\GeneralAdmin;
use App\Http\Middleware\SyncSessionUser;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        \App\Console\Commands\ScanDuplicates::class,
        \App\Console\Commands\CheckSaleInvoiceDuplicates::class,
        \App\Console\Commands\BackfillSerialPurchase::class,
    ])
    ->withProviders([
        \App\Providers\AuthServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        // Register middleware aliases early to ensure legacy string aliases
        // like `posAdmin` resolve during bootstrap and in tests where the
        // Kernel-based registration may not be wired the same way.
        $middleware->alias([
            'posAdmin' => SuperAdmin::class,
            'generalAdmin' => GeneralAdmin::class,
        ]);
        // Ensure web requests authenticate from session pos keys
        $middleware->appendToGroup('web', SyncSessionUser::class);
        // Keep test-time logger appended to the web group when testing.
        // During testing, append a lightweight request logger to the web group.
        // Avoid calling `app()` here (container may not be fully resolved),
        // use environment variables instead to determine the environment.
        $detectedEnv = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? getenv('APP_ENV');
        if ($detectedEnv === 'testing') {
            $middleware->alias(['testLogger' => \App\Http\Middleware\TestRequestLogger::class]);
            $middleware->appendToGroup('web', \App\Http\Middleware\TestRequestLogger::class);
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
