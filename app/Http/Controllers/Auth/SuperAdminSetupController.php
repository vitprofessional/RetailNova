<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Alert;

class SuperAdminSetupController extends Controller
{
    public function show()
    {
        // If already exists, the route middleware should block; but double-check
        if (AdminUser::where('role','superadmin')->exists()) {
            return redirect()->route('userLogin');
        }
        return view('auth.superadmin_setup');
    }

    public function store(Request $request)
    {
        // Block if superadmin exists
        if (AdminUser::where('role','superadmin')->exists()) {
            Alert::error('Error','Super Admin already exists');
            return redirect()->route('userLogin');
        }

        $request->validate([
            'fullName' => 'required|string|max:255',
            'mail' => 'required|email|unique:admin_users,mail',
            'password' => 'required|string|min:6',
            'storeName' => 'nullable|string|max:255',
            'contactNumber' => 'nullable|string|max:50',
        ]);

        $user = new AdminUser();
        $user->fullName = $request->fullName;
        $user->sureName = $request->sureName;
        $user->storeName = $request->storeName;
        $user->mail = $request->mail;
        $user->contactNumber = $request->contactNumber;
        $user->businessId = 1; // default, can be adjusted later
        $user->role = 'superadmin';
        $user->password = Hash::make($request->password);
        $user->save();

        // Login as admin guard and redirect to dashboard
        try { Auth::guard('admin')->login($user); } catch (\Throwable $e) {}
        Alert::success('Success','Super Admin created');
        return redirect()->route('dashboard');
    }
}
