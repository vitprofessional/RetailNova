<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureSuperAdminRole
{
    /**
     * Handle an incoming request and ensure the authenticated admin is a superadmin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = Auth::guard('admin')->user();
            if ($user && ($user->role === 'superadmin')) {
                return $next($request);
            }
        } catch (\Throwable $e) {
            // fall through to deny
        }

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        return redirect()->route('dashboard')->with('error', 'Access denied: Super Admins only');
    }
}
