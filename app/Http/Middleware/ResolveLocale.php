<?php

namespace App\Http\Middleware;

use App\Services\LocaleResolver;
use Closure;
use Illuminate\Http\Request;

class ResolveLocale
{
    public function handle(Request $request, Closure $next)
    {
        app()->setLocale(app(LocaleResolver::class)->resolve(auth('admin')->user(), $request->session()->get('locale')));
        app('translator')->setFallback('en');
        return $next($request);
    }
}
