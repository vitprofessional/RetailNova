<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use App\Services\FirstAdminBootstrap;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFirstRunOpen
{
    /** Close public first-run setup once any administrator or any business exists. */
    public function handle(Request $request, Closure $next): Response
    {
        if (FirstAdminBootstrap::firstRunOpen()) {
            return $next($request);
        }

        // A business without an administrator cannot be completed here and needs operator recovery.
        $message = AdminUser::anyAdminExists()
            ? __('authentication.first_run_closed')
            : __('authentication.first_run_incomplete');

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['error' => $message], 403);
        }

        return redirect()->route('userLogin')->with('error', $message);
    }
}
