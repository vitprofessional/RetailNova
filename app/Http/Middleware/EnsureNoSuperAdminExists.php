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
                return response()->json(['error' => __('common.api_super_admin_already_exists')], 403);
            }
            return redirect()->route('userLogin')->with('error', __('common.notification_super_admin_already_exists_please_login'));
        }
        return $next($request);
    }
}
