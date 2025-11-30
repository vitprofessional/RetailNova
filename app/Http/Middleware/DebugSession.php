<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugSession
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $cookieName = config('session.cookie');
            $cookieVal = $request->cookies->get($cookieName);
            $sid = $request->session()->getId();
            $guardUser = auth('admin')->user();
            $path = config('session.path');
            $domain = config('session.domain');
            $sameSite = config('session.same_site');
            $secure = config('session.secure');
            $httpOnly = config('session.http_only');

            Log::info('DebugSession', [
                'url' => $request->fullUrl(),
                'route' => optional($request->route())->getName(),
                'cookie_name' => $cookieName,
                'cookie_present' => $cookieVal ? true : false,
                'cookie_prefix' => $cookieVal ? substr($cookieVal, 0, 12) : null,
                'session_id' => $sid,
                'session_exists' => $sid ? true : false,
                'guard_user_id' => optional($guardUser)->id,
                'guard_user_mail' => optional($guardUser)->mail,
                'session_path' => $path,
                'session_domain' => $domain,
                'same_site' => $sameSite,
                'secure' => $secure,
                'http_only' => $httpOnly,
            ]);
        } catch (\Throwable $e) {
            Log::warning('DebugSession failed: '.$e->getMessage());
        }

        return $next($request);
    }
}
