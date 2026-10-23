<?php

namespace App\Http\Middleware;

use App\Support\BusinessContext;
use Closure;
use Illuminate\Http\Request;

/** Turns on the cross-business write guard for the duration of one web request. */
class EnforceTenantWrites
{
    public function handle(Request $request, Closure $next)
    {
        $previous = BusinessContext::$enforceWrites;
        BusinessContext::$enforceWrites = true;
        try {
            return $next($request);
        } finally {
            BusinessContext::$enforceWrites = $previous;
        }
    }
}
