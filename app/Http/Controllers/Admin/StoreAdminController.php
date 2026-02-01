<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessLocation;

class StoreAdminController extends Controller
{
    public function index()
    {
        $locations = BusinessLocation::orderBy('is_main_location','desc')->orderBy('name')->paginate(20);
        return view('admin.stores.index', compact('locations'));
    }
}
