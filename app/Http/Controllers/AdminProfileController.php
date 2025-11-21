<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\AdminUser;

class AdminProfileController extends Controller
{
    // Show current admin profile
    public function show(Request $request)
    {
        $admin = auth('admin')->user();
        // compute a safe URL for avatar: prefer the public/storage path when available
        $avatarUrl = null;
        if (!empty($admin->avatar) && Storage::disk('public')->exists($admin->avatar)) {
            $file = storage_path('app/public/' . $admin->avatar);
            $timestamp = @filemtime($file) ?: time();
            $root = rtrim($request->root(), '/');
            $publicPath = public_path('storage/' . $admin->avatar);
            if (file_exists($publicPath)) {
                // common XAMPP dev setup: public appears in the served path
                $avatarUrl = $root . '/public/storage/' . $admin->avatar . '?v=' . $timestamp;
            } else {
                $avatarUrl = $root . '/public/storage/' . $admin->avatar . '?v=' . $timestamp;
            }
        } else {
            $avatarUrl = rtrim($request->root(), '/') . '/public/eshop/assets/images/user/1.png';
        }

        return view('admin.profile.show', compact('admin', 'avatarUrl'));
    }

    

    // Show edit form
    public function edit(Request $request)
    {
        $admin = auth('admin')->user();
        return view('admin.profile.edit', compact('admin'));
    }

    // Update profile (name, contact, avatar)
    public function update(Request $request)
    {
        $admin = auth('admin')->user();

        $data = $request->validate([
            'fullName' => 'required|string|max:255',
            'sureName' => 'nullable|string|max:255',
            'mail' => 'required|email|max:255|unique:admin_users,mail,'.$admin->id,
            'contactNumber' => 'nullable|string|max:50',
            'avatar' => 'nullable|image|max:2048'
        ]);

        if($request->hasFile('avatar')){
            $file = $request->file('avatar');
            $path = $file->store('avatars','public');
            // delete old avatar if exists
            if($admin->avatar){ Storage::disk('public')->delete($admin->avatar); }
            $data['avatar'] = $path;
        }

        $admin->fill($data);
        $admin->save();

        return redirect()->route('admin.profile.show')->with('success','Profile updated successfully.');
    }

    // Change password
    public function changePassword(Request $request)
    {
        $admin = auth('admin')->user();

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|confirmed|min:8'
        ]);

        if(!Hash::check($request->input('current_password'), $admin->password)){
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $admin->password = Hash::make($request->input('new_password'));
        $admin->save();

        return redirect()->route('admin.profile.show')->with('success','Password changed successfully.');
    }
}
