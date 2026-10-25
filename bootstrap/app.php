<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\SuperAdmin;
use App\Http\Middleware\GeneralAdmin;
use App\Http\Middleware\SalesOnly;
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
        \App\Console\Commands\AuditLegacyPurchaseOwnership::class,
        \App\Console\Commands\AuditLegacyPurchaseDependencies::class,
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
            'salesOnly' => SalesOnly::class,
        ]);
        // Ensure web requests authenticate from session pos keys
        $middleware->appendToGroup('web', SyncSessionUser::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\ResolveLocale::class);
        // Business users can never write rows into another business during a request.
        $middleware->appendToGroup('web', \App\Http\Middleware\EnforceTenantWrites::class);
        // Sanitize stale asset referrers before any controller can call redirect()->back().
        $middleware->appendToGroup('web', \App\Http\Middleware\SafeBackRedirect::class);
        // Only convert back-style redirects for unassigned tenant users; preserve 403/404 responses.
        $middleware->appendToGroup('web', \App\Http\Middleware\RequireBusinessAssociation::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Never echo the first-run setup secret back through flashed old input.
        $exceptions->dontFlash('setup_token');
    })->create();
