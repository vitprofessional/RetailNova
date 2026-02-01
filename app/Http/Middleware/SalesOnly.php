<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class SalesOnly
{
    /**
     * Currently no restrictions for 'salesmanager'. Middleware is a no-op.
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
