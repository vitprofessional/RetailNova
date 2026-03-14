<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessSetup;
use App\Models\BusinessLocation;
use Database\Seeders\ComputerShopSeeder;
use Database\Seeders\ElectronicsPartsSeeder;
use Database\Seeders\GarmentsShopSeeder;
use Database\Seeders\MobileShopSeeder;
use Database\Seeders\PharmacyShopSeeder;
use Database\Seeders\VehicleShopSeeder;
use Alert;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class businessController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $actor = Auth::guard('admin')->user();
            if ($actor && in_array(strtolower($actor->role), ['storemanager'])) {
                abort(403, 'Unauthorized');
            }
            return $next($request);
        });
    }
    //business setup page 
    public function addBusinessSetupPage(){
        $business = BusinessSetup::orderBy("id","desc")->first();
        if(!$business) {
            $business = new BusinessSetup();
        }

        return view('business.businessSetup',[
            'business' => $business,
            'businessTypes' => $this->businessTypeOptions(),
            'canSeedDemoData' => $this->canSeedDemoDataForBusiness((int) ($business->id ?? 1)),
        ]);        
    }

    public function saveBusiness(Request $requ){
        $selectedBusinessType = $requ->input('businessType');
        $businessTypes = $this->businessTypeOptions();
        $isValidBusinessType = empty($selectedBusinessType) || array_key_exists($selectedBusinessType, $businessTypes);

        if (!$isValidBusinessType) {
            Alert::error("Error", "Invalid business type selected");
            return back()->withInput();
        }

        if($requ->businessId):
            $business = BusinessSetup::find($requ->businessId);
        else:
            $business = new BusinessSetup();
        endif;

        $previousBusinessType = $business->businessType;
        $shouldAttemptDemoSeed = (bool) $requ->boolean('seedBusinessDemoData') && !empty($selectedBusinessType);

        $business->businessName     = $requ->businessName;
        $business->businessLocation = $requ->businessLocation;
        $business->mobile           = $requ->mobile;
        $business->email            = $requ->mail;
        $business->tinCert          = $requ->tinCert;
        $business->invoiceFooter    = $requ->invoiceFooter;
        $business->website          = $requ->website;
        $business->facebook         = $requ->fbPage;
        $business->twitter          = $requ->twitter;
        $business->youtube          = $requ->youtubeChannel;
        $business->linkedin         = $requ->linkedin;
        $business->businessType     = $selectedBusinessType;
        $business->currencySymbol   = $requ->currencySymbol ?? $business->currencySymbol;
        $business->currencyPosition = $requ->currencyPosition ?? $business->currencyPosition ?? 'left';
        $business->currencyNegParentheses = isset($requ->currencyNegParentheses) ? (bool)$requ->currencyNegParentheses : ($business->currencyNegParentheses ?? true);
        // Show/hide Terms & Conditions: checkbox sends value only when checked
        // Use explicit default to 0 so unchecking correctly disables it
        $business->invoice_terms_enabled = (bool)$requ->input('invoiceTermsEnabled', 0);
        // Editable Terms & Conditions text
        $business->invoice_terms_text = $requ->invoiceTermsText ?? $business->invoice_terms_text;
        if($business->save()):
            $seedMessage = null;
            $savedBusinessId = (int) ($business->id ?? 1);

            if ($shouldAttemptDemoSeed) {
                if ($savedBusinessId !== 1) {
                    $seedMessage = 'Business details saved. Automatic demo seeding currently supports the primary business setup only.';
                } elseif (!$this->canSeedDemoDataForBusiness($savedBusinessId)) {
                    $seedMessage = 'Business details saved. Demo data was not seeded because products, customers, purchases, or sales already exist for this business.';
                } elseif (!empty($previousBusinessType) && $previousBusinessType !== $selectedBusinessType) {
                    $seedMessage = 'Business details saved. Demo data was not reseeded because this business already has a different saved business type.';
                } else {
                    $seedMessage = $this->seedDemoDataForBusinessType($selectedBusinessType);
                }
            }

            // Persist walk-in invoice UI toggles to .env so config picks them up
            try {
                $hideAck = (bool)$requ->input('hideAckWalkin', 0);
                $hideSig = (bool)$requ->input('hideSignaturesWalkin', 0);
                $this->updateEnv([
                    'POS_HIDE_ACK_WALKIN' => $hideAck ? 'true' : 'false',
                    'POS_HIDE_SIGNATURES_WALKIN' => $hideSig ? 'true' : 'false',
                ]);
                // Refresh config cache so changes take effect immediately
                try { Artisan::call('config:clear'); Artisan::call('config:cache'); } catch(\Throwable $e) {}
            } catch(\Throwable $e) {
                \Log::warning('Failed to update walk-in toggles in .env', ['error' => $e->getMessage()]);
            }
            Alert::success("Success!", $seedMessage ?? "Business data saved successfully");
            return back();
        else:
            Alert::error("Sorry!","Business data failed to save");
            return back();
        endif;
    }

    /**
     * Safely update key-value pairs in the .env file.
     */
    protected function updateEnv(array $data): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) return;
        $env = file_get_contents($envPath);
        foreach ($data as $key => $value) {
            $pattern = "/^" . preg_quote($key, '/') . "=.*/m";
            $line = $key . '=' . $value;
            if (preg_match($pattern, $env)) {
                $env = preg_replace($pattern, $line, $env);
            } else {
                $env .= PHP_EOL . $line;
            }
        }
        file_put_contents($envPath, $env);
    }

    protected function businessTypeOptions(): array
    {
        return [
            'mobile_shop' => 'Mobile Shop',
            'vehicle_shop' => 'Vehicle Shop',
            'computer_shop' => 'Computer Shop',
            'electronics_parts_shop' => 'Electronics Parts Shop',
            'garments_shop' => 'Garments Shop',
            'pharmacy_shop' => 'Pharmacy Shop',
        ];
    }

    protected function businessTypeSeederMap(): array
    {
        return [
            'mobile_shop' => MobileShopSeeder::class,
            'vehicle_shop' => VehicleShopSeeder::class,
            'computer_shop' => ComputerShopSeeder::class,
            'electronics_parts_shop' => ElectronicsPartsSeeder::class,
            'garments_shop' => GarmentsShopSeeder::class,
            'pharmacy_shop' => PharmacyShopSeeder::class,
        ];
    }

    protected function canSeedDemoDataForBusiness(int $businessId): bool
    {
        if ($businessId !== 1) {
            return false;
        }

        return !DB::table('products')->where('businessId', $businessId)->exists()
            && !DB::table('customers')->where('businessId', $businessId)->exists()
            && !DB::table('product_stocks')->where('businessId', $businessId)->exists()
            && !DB::table('purchase_products')->where('businessId', $businessId)->exists()
            && !DB::table('sale_products')->where('businessId', $businessId)->exists();
    }

    protected function seedDemoDataForBusinessType(string $businessType, int $targetBusinessId = 1): string
    {
        $seeders = $this->businessTypeSeederMap();
        $businessTypes = $this->businessTypeOptions();
        $seederClass = $seeders[$businessType] ?? null;

        if (!$seederClass) {
            return 'Business details saved, but no demo seeder is mapped for the selected business type.';
        }

        try {
            $seeder = app()->make($seederClass);
            $seeder->businessId = $targetBusinessId;
            app()->call([$seeder, 'run']);

            return 'Business data saved and ' . ($businessTypes[$businessType] ?? 'selected') . ' demo data seeded successfully.';
        } catch (\Throwable $e) {
            \Log::error('Business demo seeding failed', [
                'business_type' => $businessType,
                'target_business_id' => $targetBusinessId,
                'error' => $e->getMessage(),
            ]);

            return 'Business data saved, but demo data seeding failed: ' . $e->getMessage();
        }
    }

    public function saveBusinessLogo(Request $requ){
        if(!isset($requ->businessId) || empty($requ->businessId)):
            Alert::error("Error","Business ID is missing");
            return back();
        endif;
        
        $business = BusinessSetup::find($requ->businessId);
        
        if(!$business):
            Alert::error("Error","Business setup not found");
            return back();
        endif;
        
        if($requ->hasFile('businessLogo')):

            $file       = $requ->file('businessLogo');
            // $filename   = time() . '_' . $file->getClientOriginalName();
            $upFile     = $file->hashName();
            $destinationPath = public_path('uploads/business');

            // Create directory if not exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $upFile);

            $business->businessLogo = $upFile;
            if($business->save()):
                Alert::success("Success","Business logo updated");
                return back();
            else:
                Alert::error("Sorry","Business logo failed to update");
                return back();
            endif;
        endif;
    }

    public function delBusinessLogo($id){
        try {
            $business = BusinessSetup::find($id);
            if($business && $business->businessLogo):
                Storage::delete('public/uploads/business/'.$business->businessLogo);
                $business->businessLogo = null;
                $business->save();
                Alert::success("Success","Business logo deleted successfully");
            endif;
        } catch(\Exception $e) {
            Alert::error("Error","Failed to delete logo");
        }
        return back();
    }

    // Business Locations Management
    public function locationsList(){
        $locations = BusinessLocation::orderBy('is_main_location', 'desc')
                                      ->orderBy('created_at', 'desc')
                                      ->paginate(15);
        return view('business.locations.index', ['locations' => $locations]);
    }

    public function createLocation(){
        return view('business.locations.create');
    }

    public function storeLocation(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'manager_name' => 'nullable|string|max:255',
            'is_main_location' => 'nullable|boolean',
            'status' => 'nullable|boolean',
        ]);

        try {
            // If this is set as main location, unset others
            if($request->is_main_location) {
                BusinessLocation::where('is_main_location', true)->update(['is_main_location' => false]);
            }

            $location = BusinessLocation::create([
                'name' => $request->name,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'phone' => $request->phone,
                'email' => $request->email,
                'manager_name' => $request->manager_name,
                'is_main_location' => $request->is_main_location ?? false,
                'status' => $request->status ?? true,
                'description' => $request->description,
            ]);

            Alert::success("Success!", "Business location created successfully");
            return redirect()->route('business.locations');
        } catch(\Exception $e) {
            Alert::error("Error", "Failed to create location: " . $e->getMessage());
            return back()->withInput();
        }
    }

    public function editLocation($id){
        $location = BusinessLocation::findOrFail($id);
        return view('business.locations.edit', ['location' => $location]);
    }

    public function updateLocation(Request $request, $id){
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'manager_name' => 'nullable|string|max:255',
            'is_main_location' => 'nullable|boolean',
            'status' => 'nullable|boolean',
        ]);

        try {
            $location = BusinessLocation::findOrFail($id);

            // If this is set as main location, unset others
            if($request->is_main_location && !$location->is_main_location) {
                BusinessLocation::where('is_main_location', true)->update(['is_main_location' => false]);
            }

            $location->update([
                'name' => $request->name,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'phone' => $request->phone,
                'email' => $request->email,
                'manager_name' => $request->manager_name,
                'is_main_location' => $request->is_main_location ?? false,
                'status' => $request->status ?? true,
                'description' => $request->description,
            ]);

            Alert::success("Success!", "Business location updated successfully");
            return redirect()->route('business.locations');
        } catch(\Exception $e) {
            Alert::error("Error", "Failed to update location: " . $e->getMessage());
            return back()->withInput();
        }
    }

    public function deleteLocation($id){
        try {
            $location = BusinessLocation::findOrFail($id);
            
            // Don't allow deletion if it's the main location
            if($location->is_main_location) {
                Alert::warning("Warning", "Cannot delete the main business location");
                return back();
            }

            $location->delete();
            Alert::success("Success!", "Business location deleted successfully");
            return redirect()->route('business.locations');
        } catch(\Exception $e) {
            Alert::error("Error", "Failed to delete location: " . $e->getMessage());
            return back();
        }
    }

    /**
     * Wipe all demo/operational data for business ID 1 and re-seed with a
     * new business type.  Restricted to SuperAdmin only.
     */
    public function resetDemoData(Request $request)
    {
        $actor = Auth::guard('admin')->user();
        if (!$actor || strtolower($actor->role) !== 'superadmin') {
            Alert::error("Forbidden", "Only Super Admin can reset demo data.");
            return back();
        }

        $newType = $request->input('newBusinessType');
        $businessTypes = $this->businessTypeOptions();

        if (empty($newType) || !array_key_exists($newType, $businessTypes)) {
            Alert::error("Error", "Please select a valid business type to re-seed.");
            return back();
        }

        $business = BusinessSetup::find(1);
        if (!$business) {
            Alert::error("Error", "Primary business record not found.");
            return back();
        }

        DB::beginTransaction();
        try {
            // Wipe all demo data for businessId = 1 (in safe deletion order)
            DB::table('invoice_items')->whereIn(
                'saleId',
                DB::table('sale_products')->where('businessId', 1)->pluck('id')
            )->delete();
            DB::table('sale_products')->where('businessId', 1)->delete();
            DB::table('purchase_products')->where('businessId', 1)->delete();
            DB::table('product_stocks')->where('businessId', 1)->delete();
            DB::table('products')->where('businessId', 1)->delete();
            DB::table('customers')->where('businessId', 1)->delete();
            // Suppliers have no businessId column – leave them to avoid data loss
            // Brands and categories are shared; leave them too

            // Update business type
            $business->businessType = $newType;
            $business->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Reset demo data failed during wipe', ['error' => $e->getMessage()]);
            Alert::error("Error", "Failed to clear existing data: " . $e->getMessage());
            return back();
        }

        // Seed fresh demo data for the new type
        $seedMessage = $this->seedDemoDataForBusinessType($newType, 1);
        Alert::success("Success!", $seedMessage);
        return back();
    }
}
