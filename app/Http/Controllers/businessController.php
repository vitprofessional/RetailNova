<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessSetup;
use App\Models\BusinessLocation;
use Alert;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Artisan;

class businessController extends Controller
{
    //business setup page 
    public function addBusinessSetupPage(){
        $business = BusinessSetup::orderBy("id","desc")->first();
        if(!$business) {
            $business = new BusinessSetup();
        }
        return view('business.businessSetup',['business'=>$business]);        
    }

    public function saveBusiness(Request $requ){
        if($requ->businessId):
            $business = BusinessSetup::find($requ->businessId);
        else:
            $business = new BusinessSetup();
        endif;

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
        $business->currencySymbol   = $requ->currencySymbol ?? $business->currencySymbol;
        $business->currencyPosition = $requ->currencyPosition ?? $business->currencyPosition ?? 'left';
        $business->currencyNegParentheses = isset($requ->currencyNegParentheses) ? (bool)$requ->currencyNegParentheses : ($business->currencyNegParentheses ?? true);
        // Show/hide Terms & Conditions: checkbox sends value only when checked
        // Use explicit default to 0 so unchecking correctly disables it
        $business->invoice_terms_enabled = (bool)$requ->input('invoiceTermsEnabled', 0);
        // Editable Terms & Conditions text
        $business->invoice_terms_text = $requ->invoiceTermsText ?? $business->invoice_terms_text;
        if($business->save()):
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
            Alert::success("Success!","Business data saved successfully");
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
}
