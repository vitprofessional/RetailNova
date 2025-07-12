<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminUser;
use Hash;
use Session;


class userInfo extends Controller
{
    //user login & rge form str
    public function userLogin(){
        $server = AdminUser::orderby('id','DESC')->limit(1);
        return view('userInfo.userLogin',['server'=>$server]);
    }

    public function adminLogin(Request $req){
        $server = AdminUser::Where(['mail'=>$req->userMail])->first();
        if(!empty($server)):
            if(Hash::check($req->password,$server->password)):
                session()->flush();
                session(['pos'=>$server->id]);
                session()->put('pos',$server->id);
                return redirect(route('dashboard'));
            else:
                return back()->with('error',"Wrong password provided");
            endif;
        else:
            return back()->with('error',"User not exist");
        endif;
        
    }

    public function creatAdmin(Request $req){
        $server = new AdminUser();
        
        $hashPass = Hash::make($req->password);
        if($req->password != $req->confirmPass):
            return back()->with('error','Password not match confirm password');
        endif;

        $server->fullName       = $req->fullName;
        $server->sureName       = $req->sureName;
        $server->storeName      = $req->storeName;
        $server->mail           = $req->mail;
        $server->contactNumber  = $req->contactNumber;
        $server->password       = $hashPass;
        $server->businessId     = $req->businessId;
        
            if($server->save()):
                return back()->with('success','Success! Admin profile created successfully');
            else:
                return back()->with('success','error! There was an error. Please try later');
            endif;
         
    }

    public function logout(){
        Session::flush();
        Session::regenerate();
        return redirect(route('userLogin'))->with('success','logout successful');
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

    
    
    public function storeCreat(){
        return view('userInfo.storeCreat');
    }
}
