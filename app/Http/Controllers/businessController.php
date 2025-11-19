<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessSetup;
use Alert;
use Illuminate\Support\Facades\Storage; 

class businessController extends Controller
{
    //business setup page 
    public function addBusinessSetupPage(){
        $business = BusinessSetup::orderBy("id","desc")->first();
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
        if($business->save()):
            Alert::success("Success!","Business data saved successfully");
            return back();
        else:
            Alert::error("Sorry!","Business data failed to save");
            return back();
        endif;
    }

    public function saveBusinessLogo(Request $requ){
        $business = BusinessSetup::find($requ->businessId);
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
}
