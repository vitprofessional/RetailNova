<?php

namespace App\Auditing\Resolvers;

use OwenIt\Auditing\Contracts\UserResolver;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\User;

class SessionUserResolver implements UserResolver
{
    /**
     * Resolve the current user for auditing.
     */
    public static function resolve(): ?Authenticatable
    {
        // Primary: normal authenticated user
        $authUser = Auth::user();
        if ($authUser) {
            return $authUser;
        }

        // Fallback: session based POS identifiers
        $id = Session::get('pos_user_id') ?? Session::get('pos');
        if ($id) {
            return User::find($id) ?: null;
        }

        return null;
    }
}
