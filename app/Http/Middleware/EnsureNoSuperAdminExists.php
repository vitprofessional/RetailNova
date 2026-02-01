<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AdminUser;

class EnsureNoSuperAdminExists
{
    /**
     * If a superadmin already exists, block access to setup.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $exists = AdminUser::where('role', 'superadmin')->exists();
        if ($exists) {
            // If superadmin exists, redirect away
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['error' => 'Super Admin already exists'], 403);
            }
            return redirect()->route('userLogin')->with('error', 'Super Admin already exists. Please login.');
        }
        return $next($request);
    }
}
