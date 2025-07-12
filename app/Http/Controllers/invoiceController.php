<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class invoiceController extends Controller
{
    
    //add bamage product list
    public function invoicePage(){
        return view('invoice.invoicePage');
   }
}
