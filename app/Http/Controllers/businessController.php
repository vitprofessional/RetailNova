<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class businessController extends Controller
{
   

      //business setup page 
    public function addBusinessSetupPage(){
        return view('business.businessSetup');

        
    }
}
