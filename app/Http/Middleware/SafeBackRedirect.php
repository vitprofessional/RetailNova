<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Prevent a stale asset URL from becoming the target of Laravel's back() helper. */
class SafeBackRedirect
{
    public static function isUnsafe(?string $url): bool
    {
        if ($url === null || $url === '') {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path)) {
            return false;
        }

        return preg_match('~(?:^|/)service-worker(?:\.[^/]*)?$~i', $path) === 1
            || preg_match('~\.(?:js|css|map|png|jpg|webp|ico|json|webmanifest)$~i', $path) === 1;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $previous = $request->session()->previousUrl();
        $referer = $request->headers->get('referer');

        if (self::isUnsafe($previous) || self::isUnsafe($referer)) {
            $dashboard = route('dashboard');
            $request->session()->setPreviousUrl($dashboard);
            $request->headers->set('referer', $dashboard);
        }

        return $next($request);
    }
}
