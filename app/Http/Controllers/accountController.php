<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class accountController extends Controller
{
    
     public function addAccount(){
        return view('account.addAccount');
    }

     public function accountList(){
        return view('account.accountList');
    }

     public function accountReport(){
        return view('account.accountReport');
    }

}
