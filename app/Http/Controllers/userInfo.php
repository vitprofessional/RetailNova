<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminUser;
use App\Models\BusinessSetup;
use Hash;
use Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;


class userInfo extends Controller
{
    //user login & rge form str
    public function userLogin(){
        $business = \App\Models\BusinessSetup::orderBy('id','DESC')->limit(1)->get();
        $hasAdminUsers = \App\Models\AdminUser::exists();
        return view('userInfo.userLogin',['business'=>$business, 'hasAdminUsers' => $hasAdminUsers]);
    }

    public function adminLogin(Request $req){
        $credentials = [
            'mail' => $req->userMail,
            'password' => $req->password,
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

        return back()->with('error', 'Invalid credentials');
        
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
        // logout admin guard if used
        Auth::guard('admin')->logout();
        Session::forget('pos');
        Session::forget('pos_user_id');
        Session::invalidate();
        Session::regenerateToken();
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

    public function saveStoreSetup(Request $request)
    {
        $request->validate([
            'businessName' => 'required|string|max:255',
            'businessType' => 'nullable|in:mobile_shop,vehicle_shop,computer_shop,electronics_parts_shop,hardware_shop,dealership_shop,dellership_shop,general_shop,stationary_shop,stationery_shop,garments_shop,pharmacy_shop,toy_shop',
            'mobile'       => 'nullable|string|max:30',
            'mail'         => 'nullable|email|max:255',
            'businessLocation' => 'nullable|string|max:500',
            'website'      => 'nullable|url|max:255',
        ]);

        // Create or update the first (and usually only) business record
        $business = BusinessSetup::first() ?? new BusinessSetup();
        $business->businessName     = $request->businessName;
        $business->mobile           = $request->mobile;
        $business->email            = $request->mail;
        $business->businessLocation = $request->businessLocation;
        $business->website          = $request->website;
        if (!empty($request->businessType)) {
            $business->businessType = $request->businessType;
        }
        $business->save();

        // Optionally seed demo data (only on fresh install – no existing products/customers)
        if ($request->boolean('seedDemoData') && !empty($request->businessType)) {
            $hasData = \Illuminate\Support\Facades\DB::table('products')
                ->where('businessId', $business->id ?? 1)
                ->exists();

            if (!$hasData) {
                $seederMap = [
                    'mobile_shop'            => \Database\Seeders\MobileShopSeeder::class,
                    'vehicle_shop'           => \Database\Seeders\VehicleShopSeeder::class,
                    'computer_shop'          => \Database\Seeders\ComputerShopSeeder::class,
                    'electronics_parts_shop' => \Database\Seeders\ElectronicsPartsSeeder::class,
                    'hardware_shop'          => \Database\Seeders\HardwareShopSeeder::class,
                    'dealership_shop'        => \Database\Seeders\DealershipShopSeeder::class,
                    'dellership_shop'        => \Database\Seeders\DellershipShopSeeder::class,
                    'general_shop'           => \Database\Seeders\GeneralShopSeeder::class,
                    'stationary_shop'        => \Database\Seeders\StationaryShopSeeder::class,
                    'stationery_shop'        => \Database\Seeders\StationeryShopSeeder::class,
                    'garments_shop'          => \Database\Seeders\GarmentsShopSeeder::class,
                    'pharmacy_shop'          => \Database\Seeders\PharmacyShopSeeder::class,
                    'toy_shop'               => \Database\Seeders\ToyShopSeeder::class,
                ];
                $seederClass = $seederMap[$request->businessType] ?? null;
                if ($seederClass) {
                    try {
                        $seeder = app()->make($seederClass);
                        $seeder->businessId = (int) ($business->id ?? 1);
                        app()->call([$seeder, 'run']);
                    } catch (\Throwable $e) {
                        \Log::error('First-run demo seeding failed', ['error' => $e->getMessage()]);
                    }
                }
            }
        }

        return redirect()->route('userLogin')
            ->with('success', 'Business setup saved successfully! You can now log in.');
    }
}
