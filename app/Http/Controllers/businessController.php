<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessSetup;
use App\Models\BusinessLocation;
use Database\Seeders\ComputerShopSeeder;
use Database\Seeders\DealershipShopSeeder;
use Database\Seeders\DellershipShopSeeder;
use Database\Seeders\ElectronicsPartsSeeder;
use Database\Seeders\GeneralShopSeeder;
use Database\Seeders\GarmentsShopSeeder;
use Database\Seeders\HardwareShopSeeder;
use Database\Seeders\MobileShopSeeder;
use Database\Seeders\PharmacyShopSeeder;
use Database\Seeders\StationaryShopSeeder;
use Database\Seeders\StationeryShopSeeder;
use Database\Seeders\ToyShopSeeder;
use Database\Seeders\VehicleShopSeeder;
use Alert;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        $business->hide_invoice_acknowledgement = (bool) $requ->input('hideInvoiceAcknowledgement', 0);
        // Show/hide Terms & Conditions: checkbox sends value only when checked
        // Use explicit default to 0 so unchecking correctly disables it
        $business->invoice_terms_enabled = (bool)$requ->input('invoiceTermsEnabled', 0);
        // Editable Terms & Conditions text
        $business->invoice_terms_text = $requ->invoiceTermsText ?? $business->invoice_terms_text;
        if($business->save()):
            $seedMessage = null;
            $savedBusinessId = (int) ($business->id ?? 1);

            if ($shouldAttemptDemoSeed) {
                if (app()->environment('production')) {
                    $seedMessage = 'Business details saved. Demo data import is disabled in production.';
                } elseif (!$this->canSeedDemoDataForBusiness($savedBusinessId)) {
                    $seedMessage = 'Business details saved. Demo data was not seeded because products, customers, purchases, or sales already exist for this business.';
                } elseif (!empty($previousBusinessType) && $previousBusinessType !== $selectedBusinessType) {
                    $seedMessage = 'Business details saved. Demo data was not reseeded because this business already has a different saved business type.';
                } else {
                    $seedMessage = $this->seedDemoDataForBusinessType($selectedBusinessType, $savedBusinessId);
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
            'hardware_shop' => 'Hardware Shop',
            'dealership_shop' => 'Dealership Shop',
            'dellership_shop' => 'Dellership Shop',
            'general_shop' => 'General Shop',
            'stationary_shop' => 'Stationary Shop',
            'garments_shop' => 'Garments Shop',
            'pharmacy_shop' => 'Pharmacy Shop',
            'toy_shop' => 'Toy Shop',
        ];
    }

    protected function businessTypeSeederMap(): array
    {
        return [
            'mobile_shop' => MobileShopSeeder::class,
            'vehicle_shop' => VehicleShopSeeder::class,
            'computer_shop' => ComputerShopSeeder::class,
            'electronics_parts_shop' => ElectronicsPartsSeeder::class,
            'hardware_shop' => HardwareShopSeeder::class,
            'dealership_shop' => DealershipShopSeeder::class,
            'dellership_shop' => DellershipShopSeeder::class,
            'general_shop' => GeneralShopSeeder::class,
            'stationary_shop' => StationaryShopSeeder::class,
            'stationery_shop' => StationeryShopSeeder::class,
            'garments_shop' => GarmentsShopSeeder::class,
            'pharmacy_shop' => PharmacyShopSeeder::class,
            'toy_shop' => ToyShopSeeder::class,
        ];
    }

    protected function canSeedDemoDataForBusiness(int $businessId): bool
    {
        if ($businessId <= 0) {
            return false;
        }

        $tables = ['products', 'customers', 'product_stocks', 'purchase_products', 'sale_products'];
        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'businessId')) {
                return false;
            }
        }

        return !DB::table('products')->where('businessId', $businessId)->exists()
            && !DB::table('customers')->where('businessId', $businessId)->exists()
            && !DB::table('product_stocks')->where('businessId', $businessId)->exists()
            && !DB::table('purchase_products')->where('businessId', $businessId)->exists()
            && !DB::table('sale_products')->where('businessId', $businessId)->exists();
    }

    protected function wipeBusinessOperationalData(int $businessId): void
    {
        if ($businessId <= 0) {
            throw new \RuntimeException('Invalid business selected for reset.');
        }

        $required = ['products', 'customers', 'product_stocks', 'purchase_products', 'sale_products'];
        foreach ($required as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'businessId')) {
                throw new \RuntimeException('Cannot safely reset selected store. Missing businessId on table: ' . $tableName);
            }
        }

        $saleIds = DB::table('sale_products')->where('businessId', $businessId)->pluck('id');
        $purchaseIds = DB::table('purchase_products')->where('businessId', $businessId)->pluck('id');
        $productIds = DB::table('products')->where('businessId', $businessId)->pluck('id');
        $customerIds = DB::table('customers')->where('businessId', $businessId)->pluck('id');

        if (Schema::hasTable('return_sale_items') && $saleIds->isNotEmpty()) {
            DB::table('return_sale_items')->whereIn('saleId', $saleIds)->delete();
        }
        if (Schema::hasTable('sale_returns') && $saleIds->isNotEmpty()) {
            DB::table('sale_returns')->whereIn('saleId', $saleIds)->delete();
        }

        if (Schema::hasTable('invoice_items')) {
            if (Schema::hasColumn('invoice_items', 'businessId')) {
                DB::table('invoice_items')->where('businessId', $businessId)->delete();
            } elseif ($saleIds->isNotEmpty()) {
                DB::table('invoice_items')->whereIn('saleId', $saleIds)->delete();
            }
        }

        if (Schema::hasTable('product_serials')) {
            $serialDelete = DB::table('product_serials');
            $canDelete = false;
            if (Schema::hasColumn('product_serials', 'purchaseId') && $purchaseIds->isNotEmpty()) {
                $serialDelete->whereIn('purchaseId', $purchaseIds);
                $canDelete = true;
            }
            if (Schema::hasColumn('product_serials', 'saleId') && $saleIds->isNotEmpty()) {
                $serialDelete->{$canDelete ? 'orWhereIn' : 'whereIn'}('saleId', $saleIds);
                $canDelete = true;
            }
            if (!$canDelete && Schema::hasColumn('product_serials', 'productId') && $productIds->isNotEmpty()) {
                $serialDelete->whereIn('productId', $productIds);
                $canDelete = true;
            }
            if ($canDelete) {
                $serialDelete->delete();
            }
        }

        if (Schema::hasTable('return_purchase_items') && $purchaseIds->isNotEmpty()) {
            DB::table('return_purchase_items')->whereIn('purchaseId', $purchaseIds)->delete();
        }
        if (Schema::hasTable('purchase_returns') && $purchaseIds->isNotEmpty()) {
            DB::table('purchase_returns')->whereIn('purchaseId', $purchaseIds)->delete();
        }

        if ($saleIds->isNotEmpty()) {
            DB::table('sale_products')->whereIn('id', $saleIds)->delete();
        }
        if ($purchaseIds->isNotEmpty()) {
            DB::table('purchase_products')->whereIn('id', $purchaseIds)->delete();
        }

        if (Schema::hasColumn('product_stocks', 'businessId')) {
            DB::table('product_stocks')->where('businessId', $businessId)->delete();
        } elseif ($purchaseIds->isNotEmpty() || $productIds->isNotEmpty()) {
            $stockDelete = DB::table('product_stocks');
            $hasFilter = false;
            if (Schema::hasColumn('product_stocks', 'purchaseId') && $purchaseIds->isNotEmpty()) {
                $stockDelete->whereIn('purchaseId', $purchaseIds);
                $hasFilter = true;
            }
            if (Schema::hasColumn('product_stocks', 'productId') && $productIds->isNotEmpty()) {
                $stockDelete->{$hasFilter ? 'orWhereIn' : 'whereIn'}('productId', $productIds);
                $hasFilter = true;
            }
            if ($hasFilter) {
                $stockDelete->delete();
            }
        }

        if ($productIds->isNotEmpty()) {
            DB::table('products')->whereIn('id', $productIds)->delete();
        }
        if ($customerIds->isNotEmpty()) {
            DB::table('customers')->whereIn('id', $customerIds)->delete();
        }
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
        $requ->validate([
            'businessId' => 'required|integer|exists:business_setups,id',
            'businessLogo' => 'required|image|mimes:jpeg,jpg,png,gif,webp,svg|max:2048',
        ]);

        $business = BusinessSetup::find($requ->businessId);
        if(!$business):
            Alert::error("Error","Business setup not found");
            return back();
        endif;

        if(!$requ->hasFile('businessLogo')):
            Alert::error("Error","Please choose a valid logo file to upload.");
            return back();
        endif;

        try {
            $file = $requ->file('businessLogo');
            $upFile = $file->hashName();
            $destinationPath = public_path('uploads/business');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // Replace previous file (if any) to avoid orphan logo files.
            if (!empty($business->businessLogo)) {
                $oldPath = public_path('uploads/business/' . $business->businessLogo);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $file->move($destinationPath, $upFile);
            $business->businessLogo = $upFile;

            if($business->save()):
                Alert::success("Success","Business logo updated successfully");
                return back();
            endif;

            Alert::error("Sorry","Business logo failed to update");
            return back();
        } catch (\Throwable $e) {
            \Log::error('Business logo upload failed', [
                'business_id' => $requ->businessId,
                'error' => $e->getMessage(),
            ]);
            Alert::error("Error","Business logo upload failed: " . $e->getMessage());
            return back();
        }
    }

    public function delBusinessLogo($id){
        try {
            $business = BusinessSetup::find($id);
            if($business && $business->businessLogo):
                $logoPath = public_path('uploads/business/' . $business->businessLogo);
                if (file_exists($logoPath)) {
                    @unlink($logoPath);
                }
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
        if (app()->environment('production')) {
            Alert::error("Forbidden", "Demo data reset is disabled in production.");
            return back();
        }

        $request->validate([
            'businessId' => 'required|integer|exists:business_setups,id',
            'newBusinessType' => 'required|string',
        ]);

        $actor = Auth::guard('admin')->user();
        if (!$actor || !method_exists($actor, 'hasSuperAdminPrivileges') || !$actor->hasSuperAdminPrivileges()) {
            Alert::error("Forbidden", "Only Super Admin can reset demo data.");
            return back();
        }

        $newType = $request->input('newBusinessType');
        $targetBusinessId = (int)$request->input('businessId');
        $businessTypes = $this->businessTypeOptions();

        if (empty($newType) || !array_key_exists($newType, $businessTypes)) {
            Alert::error("Error", "Please select a valid business type to re-seed.");
            return back();
        }

        $business = BusinessSetup::find($targetBusinessId);
        if (!$business) {
            Alert::error("Error", "Selected business record not found.");
            return back();
        }

        DB::beginTransaction();
        try {
            // Wipe only the selected business data (safe scoped deletion order).
            $this->wipeBusinessOperationalData($targetBusinessId);

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
        $seedMessage = $this->seedDemoDataForBusinessType($newType, $targetBusinessId);
        Alert::success("Success!", $seedMessage);
        return back();
    }
}
