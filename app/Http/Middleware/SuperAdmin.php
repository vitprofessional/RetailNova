<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Session;
use Illuminate\Support\Facades\Auth;

class SuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If admin guard is authenticated, allow through even if legacy 'pos' session key is missing
        try{
            if(Auth::guard('admin')->check()){
                return $next($request);
            }
        }catch(\Throwable $_){ /* ignore guard check errors */ }

        if(Session::has('pos')){
            // session OK, continue
        } else {
            // For AJAX/JSON requests return a 401 JSON response instead of redirecting
            if($request->ajax() || $request->expectsJson()){
                return response()->json(['error' => 'Please login to continue'], 401);
            }

            return redirect(route('userLogin'))->with('error','Please login to continue');
        }
        return $next($request);
    }
}
