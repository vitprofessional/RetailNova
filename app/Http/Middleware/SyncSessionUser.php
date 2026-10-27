<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\User;

class SyncSessionUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            $id = Session::get('pos_user_id') ?? Session::get('pos');
            if ($id) {
                $user = User::find($id);
                if ($user) {
                    // Resolve legacy POS session users for this request only.
                    // Auth::login() writes to the web guard's session and rotates
                    // the session ID, which can invalidate concurrent AJAX calls.
                    Auth::guard('web')->setUser($user);
                } else {
                    // Drop stale session identifier
                    Session::forget('pos_user_id');
                    Session::forget('pos');
                }
            }
        }
        return $next($request);
    }
}
