<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Supplier;

class dashboardController extends Controller
{
   public function dashboard(){
      $customerOpeningTotal = (int)Customer::sum('openingBalance');
      $supplierOpeningTotal = (int)Supplier::sum('openingBalance');
      return view('dashboard', [
         'customerOpeningTotal' => $customerOpeningTotal,
         'supplierOpeningTotal' => $supplierOpeningTotal,
      ]);
   }
}
