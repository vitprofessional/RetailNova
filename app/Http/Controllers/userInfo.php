<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\AdminUser;
use App\Services\FirstAdminBootstrap;
use App\Services\LocaleResolver;


class userInfo extends Controller
{
    private const DUMMY_PASSWORD_HASH = '$2y$12$J6fOuR7YC0Jygc.sMWaOsO5xaaL9XfovV4yWMw1erZ4rNkEsP91qW';

    //user login & rge form str
    public function userLogin(){
        return view('userInfo.userLogin', ['firstRunOpen' => FirstAdminBootstrap::firstRunOpen()]);
    }

    public function adminLogin(Request $req){
        $input = $req->validate([
            'userMail' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);
        $ipKey = 'admin-login:ip:'.$req->ip();
        $loginKey = $ipKey.':user:'.mb_strtolower(trim($input['userMail']));

        if (RateLimiter::tooManyAttempts($ipKey, 20) || RateLimiter::tooManyAttempts($loginKey, 5)) {
            abort(429);
        }

        $admin = AdminUser::query()->where('mail', $input['userMail'])->first();
        $passwordHash = $admin?->password ?? self::DUMMY_PASSWORD_HASH;
        $passwordIsValid = Hash::check($input['password'], $passwordHash);

        if ($admin && $passwordIsValid) {
            // Regenerate session to prevent fixation
            $req->session()->regenerate(true);

            Auth::guard('admin')->login($admin);
            RateLimiter::clear($loginKey);
            // keep legacy session keys for other code paths
            session()->put('pos', $admin->id);
            session()->put('pos_user_id', $admin->id);

            return redirect()->route('dashboard');
        }

        RateLimiter::hit($ipKey, 60);
        RateLimiter::hit($loginKey, 60);

        return back()->with('login_notice', ['type' => 'error', 'key' => 'invalid_credentials']);
        
    }

    public function logout(Request $request){
        $guestLocale = app()->getLocale();
        if (!in_array($guestLocale, LocaleResolver::SUPPORTED, true)) {
            $guestLocale = 'en';
        }

        // Keep any separately authenticated web user in this shared browser session.
        Auth::guard('admin')->logout();
        $request->session()->forget(['pos', 'pos_user_id', 'cultivationAdmin', 'url.intended']);
        $request->session()->migrate(true);
        $request->session()->regenerateToken();
        $request->session()->put('locale', $guestLocale);

        return redirect(route('userLogin'))->with('login_notice', ['type' => 'success', 'key' => 'logout_successful']);
    }
    


    
    //user login & rge form end
    
    public function userLockScreen(){
        return view('userInfo.userLockScreen');
    }
    
    public function userRecover(){
        return view('userInfo.userRecover');
    }
    
    public function userRecoverCode(){
        return view('userInfo.recoverCode');
    }
    
    public function userRecoverPassword(){
        return view('userInfo.recoverNewPass');
    }
    
    public function userConfirmMail(){
        return view('userInfo.userConfirmMail');
    }
}
