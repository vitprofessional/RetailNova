<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Facades\Auth;
use App\Services\FirstAdminBootstrap;
use App\Services\LocaleResolver;


class userInfo extends Controller
{
    //user login & rge form str
    public function userLogin(){
        return view('userInfo.userLogin', ['firstRunOpen' => FirstAdminBootstrap::firstRunOpen()]);
    }

    public function adminLogin(Request $req){
        $input = $req->validate([
            'userMail' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);
        $credentials = [
            'mail' => $input['userMail'],
            'password' => $input['password'],
        ];

        if (Auth::guard('admin')->attempt($credentials)) {
            // Regenerate session to prevent fixation
            $req->session()->regenerate();

            $admin = Auth::guard('admin')->user();
            // keep legacy session keys for other code paths
            session()->put('pos', $admin->id);
            session()->put('pos_user_id', $admin->id);

            return redirect()->route('dashboard');
        }

        return back()->with('login_notice', ['type' => 'error', 'key' => 'invalid_credentials']);
        
    }

    public function logout(Request $request){
        $guestLocale = app()->getLocale();
        if (!in_array($guestLocale, LocaleResolver::SUPPORTED, true)) {
            $guestLocale = 'en';
        }

        // logout admin guard if used
        Auth::guard('admin')->logout();
        Session::forget('pos');
        Session::forget('pos_user_id');
        Session::invalidate();
        Session::regenerateToken();
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
