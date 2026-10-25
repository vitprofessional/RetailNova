<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessSetup;
use App\Models\BusinessLocation;
use App\Models\AdminUser;
use App\Support\BusinessTypes;
use Alert;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class businessController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $actor = Auth::guard('admin')->user();
            if ($actor && in_array(strtolower($actor->role), ['storemanager'])) {
                abort(403, __('common.api_forbidden'));
            }
            return $next($request);
        });
    }
    //business setup page
    public function addBusinessSetupPage(){
        $actor = $this->settingsActor(false);
        $business = $this->authorizedBusinessTarget($actor, request()->query('businessId'), true);

        return view('business.businessSetup',[
            'business' => $business,
            'businessTypes' => BusinessTypes::options(),
            'canSeedDemoData' => $this->canSeedDemoDataForBusiness((int) ($business->id ?? 0)),
        ]);
    }

    public function saveBusiness(Request $requ){
        $actor = $this->settingsActor();
        $requ->validate([
            'businessId' => ['nullable', 'integer'],
            'businessName' => ['required', 'string', 'max:255'],
            'businessType' => ['nullable', Rule::in(BusinessTypes::values())],
            // businessLocation and invoiceFooter are TEXT columns (2026_10_21 migration).
            'businessLocation' => ['nullable', 'string', 'max:500'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'mail' => ['nullable', 'email', 'max:255'],
            'tinCert' => ['nullable', 'string', 'max:100'],
            'invoiceFooter' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'url', 'max:255'],
            'currencySymbol' => ['nullable', 'string', 'max:16'],
            'currencyPosition' => ['nullable', 'in:left,right'],
            'fbPage' => ['nullable', 'url', 'max:255'],
            'twitter' => ['nullable', 'url', 'max:255'],
            'youtubeChannel' => ['nullable', 'url', 'max:255'],
            'linkedin' => ['nullable', 'url', 'max:255'],
            'invoiceTermsText' => ['nullable', 'string', 'max:5000'],
            'seedBusinessDemoData' => ['nullable', 'boolean'],
            'hideInvoiceAcknowledgement' => ['nullable', 'boolean'],
            'invoiceTermsEnabled' => ['nullable', 'boolean'],
            'currencyNegParentheses' => ['nullable', 'boolean'],
        ]);
        if (!$actor->hasSuperAdminPrivileges()
            && ($requ->exists('hideAckWalkin') || $requ->exists('hideSignaturesWalkin'))) {
            abort(403);
        }
        $business = $this->authorizedBusinessTarget($actor, $requ->input('businessId'), true);
        $selectedBusinessType = $requ->input('businessType');

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
        // Submitted-but-empty clears the symbol (display falls back to the default symbol);
        // a request that omits the field keeps the saved value.
        if ($requ->exists('currencySymbol')) {
            $business->currencySymbol = $requ->filled('currencySymbol') ? $requ->input('currencySymbol') : null;
        }
        $business->currencyPosition = $requ->currencyPosition ?? $business->currencyPosition ?? 'left';
        // Checkbox contract (same as the other toggles here): absent means off.
        $business->currencyNegParentheses = $requ->boolean('currencyNegParentheses');
        $business->hide_invoice_acknowledgement = (bool) $requ->input('hideInvoiceAcknowledgement', 0);
        // Show/hide Terms & Conditions: checkbox sends value only when checked
        // Use explicit default to 0 so unchecking correctly disables it
        $business->invoice_terms_enabled = (bool)$requ->input('invoiceTermsEnabled', 0);
        // Editable Terms & Conditions text; clearing it restores the default invoice terms.
        if ($requ->exists('invoiceTermsText')) {
            $business->invoice_terms_text = $requ->filled('invoiceTermsText') ? $requ->input('invoiceTermsText') : null;
        }
        if($business->save()):
            $seedMessage = null;
            $seedFailed = false;
            $savedBusinessId = (int) ($business->id ?? 0);

            if ($shouldAttemptDemoSeed) {
                if (app()->environment('production')) {
                    $seedMessage = __('common.demo_production');
                } elseif (!$this->canSeedDemoDataForBusiness($savedBusinessId)) {
                    $seedMessage = __('common.demo_existing');
                } elseif (!empty($previousBusinessType) && $previousBusinessType !== $selectedBusinessType) {
                    $seedMessage = __('common.demo_type');
                } elseif ($this->seedDemoDataForBusinessType($selectedBusinessType, $savedBusinessId)) {
                    $seedMessage = __('common.demo_seeded', ['type' => BusinessTypes::label($selectedBusinessType)]);
                } else {
                    $seedFailed = true;
                    $seedMessage = __('common.demo_seed_failed');
                }
            }

            // These switches are deployment-wide .env settings, so only the explicit platform role may change them.
            if ($actor->hasSuperAdminPrivileges() && !app()->environment('testing')) {
                try {
                    $hideAck = (bool)$requ->input('hideAckWalkin', 0);
                    $hideSig = (bool)$requ->input('hideSignaturesWalkin', 0);
                    $this->updateEnv([
                        'POS_HIDE_ACK_WALKIN' => $hideAck ? 'true' : 'false',
                        'POS_HIDE_SIGNATURES_WALKIN' => $hideSig ? 'true' : 'false',
                    ]);
                    try { Artisan::call('config:clear'); Artisan::call('config:cache'); } catch(\Throwable $e) {}
                } catch(\Throwable $e) {
                    \Log::warning('Failed to update walk-in toggles in .env', ['error' => $e->getMessage()]);
                }
            }
            if ($seedFailed) {
                Alert::warning(__('common.notification_warning'), $seedMessage);
            } else {
                Alert::success(__('common.notification_success'), $seedMessage ?? __('common.notification_business_data_saved_successfully'));
            }
            return back();
        else:
            Alert::error(__('common.notification_sorry'),__('common.notification_business_data_failed_to_save'));
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

    /** Seed demo data atomically: a failure rolls back every demo write. Returns whether it succeeded. */
    protected function seedDemoDataForBusinessType(string $businessType, int $targetBusinessId): bool
    {
        try {
            DB::transaction(fn () => $this->runDemoSeeder($businessType, $targetBusinessId));
            return true;
        } catch (\Throwable $e) {
            Log::error('Business demo seeding failed', [
                'business_type' => $businessType,
                'target_business_id' => $targetBusinessId,
                'exception' => $e,
            ]);
            return false;
        }
    }

    /** Callers own the transaction so seeding can be combined with other writes. */
    private function runDemoSeeder(string $businessType, int $targetBusinessId): void
    {
        $seederClass = BusinessTypes::seeder($businessType)
            ?? throw new \InvalidArgumentException('No demo seeder is mapped for business type: ' . $businessType);

        $seeder = app()->make($seederClass);
        $seeder->businessId = $targetBusinessId;
        app()->call([$seeder, 'run']);
    }

    public function saveBusinessLogo(Request $requ){
        $actor = $this->settingsActor();
        $requ->validate(['businessId' => ['required', 'integer']]);
        $business = $this->authorizedBusinessTarget($actor, $requ->input('businessId'), false);
        $requ->validate(['businessLogo' => 'required|image|mimes:jpeg,jpg,png,webp|max:4096']);

        if(!$requ->hasFile('businessLogo')):
            Alert::error(__('common.notification_error'),__('common.notification_please_choose_a_valid_logo_file_to_upload'));
            return back();
        endif;

        try {
            $file = $requ->file('businessLogo');
            $oldLogo = $business->businessLogo;
            $business->businessLogo = app(\App\Support\BusinessUploads::class)
                ->store($file, (int) $business->id, 'logo');

            if($business->save()):
                app(\App\Support\BusinessUploads::class)->delete($oldLogo);
                if ($oldLogo && !str_starts_with((string) $oldLogo, 'uploads/')) {
                    $legacyLogo = public_path('uploads/business/'.basename((string) $oldLogo));
                    if (is_file($legacyLogo)) @unlink($legacyLogo);
                }
                Alert::success(__('common.notification_success'),__('common.notification_business_logo_updated_successfully'));
                return back();
            endif;

            Alert::error(__('common.notification_sorry'),__('common.notification_business_logo_failed_to_update'));
            return back();
        } catch (\Throwable $e) {
            Log::error('Business logo upload failed', [
                'business_id' => $requ->businessId,
                'exception' => $e,
            ]);
            Alert::error(__('common.notification_error'),__('common.notification_business_logo_upload_failed_try_again'));
            return back();
        }
    }

    public function delBusinessLogo($id){
        $actor = $this->settingsActor();
        $business = $this->authorizedBusinessTarget($actor, $id, false);
        try {
            if($business && $business->businessLogo):
                $logoPath = $business->invoiceLogoFilesystemPath();
                if ($logoPath && file_exists($logoPath)) {
                    @unlink($logoPath);
                }
                app(\App\Support\BusinessUploads::class)->delete($business->businessLogo);
                $business->businessLogo = null;
                $business->save();
                Alert::success(__('common.notification_success'),__('common.notification_business_logo_deleted_successfully'));
            endif;
        } catch(\Exception $e) {
            Alert::error(__('common.notification_error'),__('common.notification_failed_to_delete_logo'));
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

            Alert::success(__('common.notification_success'), __('common.notification_business_location_created_successfully'));
            return redirect()->route('business.locations');
        } catch(\Exception $e) {
            Alert::error(__('common.notification_error'), __('common.notification_failed_to_create_location') . $e->getMessage());
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

            Alert::success(__('common.notification_success'), __('common.notification_business_location_updated_successfully'));
            return redirect()->route('business.locations');
        } catch(\Exception $e) {
            Alert::error(__('common.notification_error'), __('common.notification_failed_to_update_location') . $e->getMessage());
            return back()->withInput();
        }
    }

    public function deleteLocation($id){
        try {
            $location = BusinessLocation::findOrFail($id);
            
            // Don't allow deletion if it's the main location
            if($location->is_main_location) {
                Alert::warning(__('common.notification_warning'), __('common.notification_cannot_delete_the_main_business_location'));
                return back();
            }

            $location->delete();
            Alert::success(__('common.notification_success'), __('common.notification_business_location_deleted_successfully'));
            return redirect()->route('business.locations');
        } catch(\Exception $e) {
            Alert::error(__('common.notification_error'), __('common.notification_failed_to_delete_location') . $e->getMessage());
            return back();
        }
    }

    /**
     * Wipe the selected business's operational data and re-seed it with a new
     * business type. Restricted to SuperAdmin only.
     */
    public function resetDemoData(Request $request)
    {
        $actor = $this->settingsActor();
        abort_unless($actor->hasSuperAdminPrivileges(), 403);
        if (app()->environment('production')) {
            Alert::error(__('common.notification_forbidden'), __('common.notification_demo_data_reset_is_disabled_in_production'));
            return back();
        }

        $request->validate([
            'businessId' => 'required|integer|exists:business_setups,id',
            'newBusinessType' => ['required', Rule::in(BusinessTypes::values())],
        ]);

        $newType = $request->input('newBusinessType');
        $targetBusinessId = (int)$request->input('businessId');
        $business = $this->authorizedBusinessTarget($actor, $targetBusinessId, false);

        try {
            // One transaction: a failed re-seed restores the wiped data and the previous type.
            DB::transaction(function () use ($business, $newType, $targetBusinessId): void {
                $this->wipeBusinessOperationalData($targetBusinessId);
                $business->businessType = $newType;
                $business->save();
                $this->runDemoSeeder($newType, $targetBusinessId);
            });
        } catch (\Throwable $e) {
            Log::error('Demo data reset failed', [
                'business_type' => $newType,
                'target_business_id' => $targetBusinessId,
                'exception' => $e,
            ]);
            Alert::error(__('common.notification_error'), __('common.demo_reset_failed'));
            return back();
        }

        Alert::success(__('common.notification_success'), __('common.demo_reset_completed', ['type' => BusinessTypes::label($newType)]));
        return back();
    }

    private function settingsActor(bool $write = true): AdminUser
    {
        $actor = Auth::guard('admin')->user();
        $allowedRoles = $write ? ['admin', 'superadmin'] : ['admin', 'superadmin', 'gm', 'salesmanager'];
        abort_unless($actor && in_array($actor->normalizedRole(), $allowedRoles, true), 403);
        return $actor;
    }

    private function authorizedBusinessTarget(AdminUser $actor, mixed $requestedId, bool $allowCreate): BusinessSetup
    {
        $hasRequestedId = $requestedId !== null && $requestedId !== '';
        $role = $actor->normalizedRole();

        if ($role === 'superadmin') {
            if ($hasRequestedId) {
                return BusinessSetup::query()->findOrFail($requestedId);
            }
            if ($allowCreate) {
                if ($actor->businessId && BusinessSetup::query()->whereKey($actor->businessId)->exists()) {
                    return BusinessSetup::query()->findOrFail($actor->businessId);
                }
                $businesses = BusinessSetup::query()->orderBy('id')->limit(2)->get();
                abort_if($businesses->count() > 1, 409, __('settings.select_business_before_changing_settings'));
                return $businesses->first() ?? new BusinessSetup();
            }
            abort(404);
        }

        $businessId = filter_var($actor->businessId, FILTER_VALIDATE_INT);
        abort_unless($businessId !== false && (int) $businessId > 0, 403);
        if ($hasRequestedId && (!is_numeric($requestedId) || (int) $requestedId !== (int) $businessId)) {
            abort(403);
        }

        return BusinessSetup::query()->findOrFail((int) $businessId);
    }
}
