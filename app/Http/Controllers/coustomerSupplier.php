<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Http\Requests\SaveCustomerRequest;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\balancesheet;
use App\Models\SaleProduct;
use App\Models\InvoiceItem;
use App\Models\ReturnSaleItem;
use App\Models\SaleReturn;
use App\Models\PurchaseProduct;
use App\Models\PurchaseReturn;
use App\Models\ReturnPurchaseItem;
use App\Models\ProductStock;
use Alert;

class coustomerSupplier extends Controller
{
    // add coustomer
    public function addCustomer(){
        $workflow=app(\App\Services\PurchaseWorkflow::class);
        $data = $workflow->scoped(Customer::query())->orderBy('id','DESC')->get();
        $trashed = $workflow->scoped(Customer::onlyTrashed())->orderBy('id','DESC')->get();
        $openingTotal = $workflow->scoped(Customer::query())->sum('openingBalance');
        $businesses = \App\Models\BusinessSetup::when(!$workflow->platform(),fn($q)=>$q->whereKey(auth('admin')->user()->businessId))->orderBy('id','asc')->get();
        return view('customer&supplier.addCustomer',[
            'listItem'=>$data,
            'trashedList' => $trashed,
            'openingTotal' => $openingTotal,
            'businesses' => $businesses,
        ]);
    }

    //save custmer
    public function saveCustomer(SaveCustomerRequest $req){
        $workflow=app(\App\Services\PurchaseWorkflow::class);
        $data = $req->profileId ? $workflow->scoped(Customer::withoutGlobalScopes())->findOrFail($req->profileId) : new Customer();
        $requestedBusinessId=$req->input('businessId');
        if($req->profileId && ($requestedBusinessId===null || $requestedBusinessId==='')) {$requestedBusinessId=$data->businessId;}
        $businessId=$workflow->resolveBusinessId($requestedBusinessId);
        abort_if($req->profileId && (int)$data->businessId !== $businessId,403,'Customers cannot be moved between businesses through edit.');

        $data->name          = trim($req->fullName);
        $data->openingBalance= (int)$req->openingBalance;
        $data->mail          = filled($req->mail) ? strtolower(trim((string)$req->mail)) : null;
        $data->mobile        = filled($req->mobile) ? trim((string)$req->mobile) : null;
        $data->country       = trim((string)$req->country);
        $data->state         = trim((string)$req->state);
        $data->city          = trim((string)$req->city);
        $data->area          = trim((string)$req->area);

        $data->businessId=$businessId;

        if($data->save()):
            $message = $req->profileId ? __('common.customer_updated') : __('common.customer_created');
            Alert::success(__('common.notification_success'), $message);
            return redirect(route('addCustomer'));
        else:
            Alert::error(__('common.notification_failed'),__('common.notification_customer_profile_save_failed'));
            return back();
        endif;
    }

    //edit customer
     public function editCustomer($id){
        $data = app(\App\Services\PurchaseWorkflow::class)->scoped(Customer::withoutGlobalScopes())->findOrFail($id);
        return view('customer&supplier.addCustomer',['profile'=>$data,'businesses'=>\App\Models\BusinessSetup::whereKey($data->businessId)->get()]);
    }

    //delete customer
    /** "Delete all data" is permanent (including cancelled-sale history): admins only. */
    private function authorizeDeleteType(string $deleteType): void
    {
        if ($deleteType !== 'fullDelete') {
            return;
        }
        $actor = auth('admin')->user();
        abort_unless($actor instanceof \App\Models\AdminUser && $actor->canDeleteAllRelatedData(), 403,
            __('common.notification_only_admins_can_delete_all_related_data'));
    }

    public function delCustomer($id, Request $request){
        $this->authorizeDeleteType((string) $request->input('deleteType', 'profileOnly'));
        $data = Customer::withTrashed()->find($id);
        if(!$data){
            Alert::error(__('common.notification_failed'),__('common.notification_customer_not_found'));
            return back();
        }

        $deleteType = $request->input('deleteType', 'profileOnly');

        DB::beginTransaction();
        try{
            if($deleteType === 'fullDelete'){
                // Delete all related data (cascade delete)
                $this->cascadeDeleteCustomer($id);
                // finally delete customer record permanently
                $data->forceDelete();
                Alert::success(__('common.notification_deleted'),__('common.notification_customer_profile_and_all_related_records_deleted_successfully'));
            } else {
                // Delete only the profile (soft delete or just remove profile data)
                $data->delete(); // This will soft delete
                Alert::success(__('common.notification_deleted'),__('common.notification_customer_profile_deleted_successfully_related_transactions_are_preserved'));
            }
            DB::commit();
        }catch(\Throwable $e){
            DB::rollBack();
            Alert::error(__('common.notification_failed'),__('common.notification_failed_to_delete_customer').$e->getMessage());
        }
        return back();
    }

    // bulk delete customers
    public function bulkDeleteCustomer(Request $req){
        $this->authorizeDeleteType((string) $req->input('deleteType', 'profileOnly'));
        $ids = $req->input('selected', []);
        if(!is_array($ids) || count($ids) === 0){
            Alert::error(__('common.notification_no_selection'),__('common.notification_no_customers_selected_for_deletion'));
            return back();
        }

        $deleteType = $req->input('deleteType', 'profileOnly');
        $clean = array_filter(array_map('intval', $ids));
        
        DB::beginTransaction();
        try{
            foreach($clean as $cid){
                $cust = Customer::withTrashed()->find($cid);
                if(!$cust) continue;
                
                if($deleteType === 'fullDelete'){
                    // Delete all related data (cascade delete)
                    $this->cascadeDeleteCustomer($cid);
                    $cust->forceDelete();
                } else {
                    // Delete only the profile (soft delete)
                    $cust->delete();
                }
            }
            DB::commit();
            
            if($deleteType === 'fullDelete'){
                Alert::success(__('common.notification_deleted'),__('common.notification_selected_customers_and_all_related_records_deleted_successfully'));
            } else {
                Alert::success(__('common.notification_deleted'),__('common.notification_selected_customer_profiles_deleted_related_transactions_are_preserved'));
            }
        }catch(\Throwable $e){
            DB::rollBack();
            Alert::error(__('common.notification_failed'),__('common.notification_failed_to_delete_selected_customers').$e->getMessage());
        }
        return back();
    }

     // submit customer by ajax
    public function createCustomer(Request $req){
        $workflow=app(\App\Services\PurchaseWorkflow::class);
        if($workflow->platform() && !$req->filled('businessId')){
            throw \Illuminate\Validation\ValidationException::withMessages([
                'businessId' => __('purchases.business_required'),
            ]);
        }
        $payload = [
            'fullName'       => trim((string)$req->fullName),
            'mail'           => strtolower(trim((string)$req->mail)),
            'mobile'         => trim((string)$req->mobile),
            'country'        => trim((string)$req->country),
            'state'          => trim((string)$req->state),
            'city'           => trim((string)$req->city),
            'area'           => trim((string)$req->area),
            'openingBalance' => (int)($req->openingBalance ?? 0),
        ];

        $validator = Validator::make($payload, [
            'fullName'       => ['required','string','min:2','max:150'],
            'mail'           => ['nullable','email','max:190', function($attr,$value,$fail){
                                    if(!empty($value)){
                                        $exists = Customer::withTrashed()
                                            ->where('mail',$value)
                                            ->whereNull('deleted_at')
                                            ->exists();
                                        if($exists) $fail(__('common.email_taken'));
                                    }
                                }],
            'mobile'         => ['nullable','string','min:6','max:25', function($attr,$value,$fail){
                                    if(!empty($value)){
                                        $exists = Customer::withTrashed()
                                            ->where('mobile',$value)
                                            ->whereNull('deleted_at')
                                            ->exists();
                                        if($exists) $fail('The mobile has already been taken.');
                                    }
                                }],
            'country'        => ['nullable','string','max:100'],
            'state'          => ['nullable','string','max:100'],
            'city'           => ['nullable','string','max:100'],
            'area'           => ['nullable','string','max:150'],
            'openingBalance' => ['integer'],
        ]);

        if($validator->fails()){
            return [
                'data'    => '',
                'message' => __('common.api_validation_failed') . implode(', ', $validator->errors()->all()),
                'errors'  => $validator->errors()->toArray(),
            ];
        }

        $actor=auth('admin')->user();
        abort_unless($actor,401);
        $businessId=$workflow->resolveBusinessId($req->input('businessId'));
        $data = new Customer();
        $data->businessId=$businessId;
        $data->name            = $payload['fullName'];
        $data->mail            = !empty($payload['mail']) ? $payload['mail'] : null;
        $data->mobile          = !empty($payload['mobile']) ? $payload['mobile'] : null;
        $data->country         = !empty($payload['country']) ? $payload['country'] : null;
        $data->state           = !empty($payload['state']) ? $payload['state'] : null;
        $data->city            = !empty($payload['city']) ? $payload['city'] : null;
        $data->area            = !empty($payload['area']) ? $payload['area'] : null;
        $data->openingBalance  = $payload['openingBalance'];

        try{
            $saved = $data->save();
        }catch(\Throwable $e){
            return [
                'data'    => '',
                'message' => __('common.api_error_failed_to_create_customer'),
            ];
        }

        $option = '';
        if($saved){
            $getData = $workflow->scoped(Customer::query())->orderBy('id','DESC')->get();
            foreach($getData as $d){
                $option .= '<option value="'.$d->id.'">'.e($d->name).'</option>';
            }
            return [
                'data'   => $option,
                'message'=>__('common.api_success_customer_created_successfully')
            ];
        }
        return [
            'data'=> '',
            'message'=>__('common.api_error_failed_to_create_customer')
        ];
    }

    // restore soft-deleted customer
    public function restoreCustomer($id)
    {
        $cust = Customer::onlyTrashed()->find($id);
        if(!$cust){
            Alert::error(__('common.notification_failed'),__('common.notification_customer_not_found_or_not_deleted'));
            return back();
        }
        $cust->restore();
        Alert::success(__('common.notification_success'),__('common.notification_customer_restored_successfully'));
        return back();
    }

    // add supplier
    public function addSupplier(){
        $workflow=app(\App\Services\PurchaseWorkflow::class);
        $data = $workflow->scoped(Supplier::withoutGlobalScopes())->orderBy('id','DESC')->get();
        $trashed = $workflow->scoped(Supplier::withoutGlobalScopes()->onlyTrashed())->orderBy('id','DESC')->get();
        $openingTotal = $workflow->scoped(Supplier::withoutGlobalScopes())->sum('openingBalance');
        $businesses = \App\Models\BusinessSetup::when(!$workflow->platform(),fn($q)=>$q->whereKey($workflow->resolveBusinessId()))->orderBy('id','asc')->get();
        return view('customer&supplier.addSupplier',[
            'listItem'=>$data,
            'trashedList' => $trashed,
            'openingTotal' => $openingTotal,
            'businesses' => $businesses,
        ]);
    }

    //save supplier
    public function saveSupplier(Request $req){
        $workflow=app(\App\Services\PurchaseWorkflow::class);
        $isUpdate = (bool)$req->profileId;
        // Validate supplier form with uniqueness
        $req->validate([
            'fullName'      => ['required','string','min:2','max:150'],
            'mail'          => [
                'nullable','email','max:190',
                Rule::unique('suppliers','mail')->ignore($req->profileId)->where(fn($q)=>$q->whereNull('deleted_at'))
            ],
            'mobile'        => [
                'nullable','string','min:6','max:25',
                Rule::unique('suppliers','mobile')->ignore($req->profileId)->where(fn($q)=>$q->whereNull('deleted_at'))
            ],
            'country'       => ['nullable','string','max:100'],
            'state'         => ['nullable','string','max:100'],
            'city'          => ['nullable','string','max:100'],
            'area'          => ['nullable','string','max:150'],
            'openingBalance'=> ['nullable','integer'],
        ]);
        $data = $isUpdate ? $workflow->scoped(Supplier::withoutGlobalScopes())->findOrFail($req->profileId) : new Supplier();
        $requestedBusinessId=$req->input('businessId');
        if($isUpdate && ($requestedBusinessId===null || $requestedBusinessId==='')) {$requestedBusinessId=$data->businessId;}
        $businessId=$workflow->resolveBusinessId($requestedBusinessId);
        abort_if($isUpdate && (int)$data->businessId !== $businessId,403,'Suppliers cannot be moved between businesses through edit.');

        // Basic server-side sanitization and integer enforcement
        $data->name          = trim((string)$req->fullName);
        $data->openingBalance= (int)$req->openingBalance;
        $data->mail          = strtolower(trim((string)$req->mail));
        $data->mobile        = filled($req->mobile) ? trim((string)$req->mobile) : null;
        $data->country       = trim((string)$req->country);
        $data->state         = trim((string)$req->state);
        $data->city          = trim((string)$req->city);
        $data->area          = trim((string)$req->area);

        $data->businessId = $businessId;

        if($data->save()){
            $msg = $isUpdate ? __('common.supplier_updated') : __('common.supplier_created');
            Alert::success(__('common.notification_success'), $msg);
            return redirect(route('addSupplier'));
        }
        Alert::error(__('common.notification_failed'),__('common.notification_supplier_profile_save_failed'));
        return back();
    }

    //edit supplier
     public function editSupplier($id){
        $workflow=app(\App\Services\PurchaseWorkflow::class);
        $data = $workflow->scoped(Supplier::withoutGlobalScopes())->findOrFail($id);
        return view('customer&supplier.addSupplier',['profile'=>$data]);
    }

    
    //delete supplier
    public function delSupplier($id, Request $request){
        $this->authorizeDeleteType((string) $request->input('deleteType', 'profileOnly'));
        $data = Supplier::withTrashed()->find($id);
        if(!$data){
            Alert::error(__('common.notification_failed'),__('common.notification_supplier_not_found'));
            return back();
        }

        $deleteType = $request->input('deleteType', 'profileOnly');

        DB::beginTransaction();
        try{
            if($deleteType === 'fullDelete'){
                // Delete all related data (cascade delete)
                $this->cascadeDeleteSupplier($id);
                $data->forceDelete();
                Alert::success(__('common.notification_deleted'),__('common.notification_supplier_profile_and_all_related_records_deleted_successfully'));
            } else {
                // Delete only the profile (soft delete or just remove profile data)
                $data->delete(); // This will soft delete
                Alert::success(__('common.notification_deleted'),__('common.notification_supplier_profile_deleted_successfully_related_transactions_are_preserved'));
            }
            DB::commit();
        }catch(\Throwable $e){
            DB::rollBack();
            Alert::error(__('common.notification_failed'),__('common.notification_failed_to_delete_supplier').$e->getMessage());
        }
        return back();
    }

    // bulk delete suppliers
    public function bulkDeleteSupplier(Request $req){
        $this->authorizeDeleteType((string) $req->input('deleteType', 'profileOnly'));
        $ids = $req->input('selected', []);
        if(!is_array($ids) || count($ids) === 0){
            Alert::error(__('common.notification_no_selection'),__('common.notification_no_suppliers_selected_for_deletion'));
            return back();
        }
        
        $deleteType = $req->input('deleteType', 'profileOnly');
        $clean = array_filter(array_map('intval', $ids));
        
        DB::beginTransaction();
        try{
            foreach($clean as $sid){
                $sup = Supplier::withTrashed()->find($sid);
                if(!$sup) continue;
                
                if($deleteType === 'fullDelete'){
                    // Delete all related data (cascade delete)
                    $this->cascadeDeleteSupplier($sid);
                    $sup->forceDelete();
                } else {
                    // Delete only the profile (soft delete)
                    $sup->delete();
                }
            }
            DB::commit();
            
            if($deleteType === 'fullDelete'){
                Alert::success(__('common.notification_deleted'),__('common.notification_selected_suppliers_and_all_related_records_deleted_successfully'));
            } else {
                Alert::success(__('common.notification_deleted'),__('common.notification_selected_supplier_profiles_deleted_related_transactions_are_preserved'));
            }
        }catch(\Throwable $e){
            DB::rollBack();
            Alert::error(__('common.notification_failed'),__('common.notification_failed_to_delete_selected_suppliers').$e->getMessage());
        }
        return back();
    }

    /**
     * Cascade delete all records related to a customer id.
     * This performs permanent deletes across related tables.
     */
    private function cascadeDeleteCustomer(int $id){
        // Sales belonging to this customer, including cancelled (soft-deleted) ones
        $saleIds = SaleProduct::withTrashed()->where('customerId', (string)$id)->pluck('id')->toArray();

        if(!empty($saleIds)){
            // invoice items referencing those sales
            InvoiceItem::withTrashed()->whereIn('saleId', $saleIds)->forceDelete();
            // return sale items referencing those sales
            ReturnSaleItem::whereIn('saleId', $saleIds)->delete();
            // sale returns (saleId column is text) - match as string
            SaleReturn::whereIn('saleId', array_map('strval',$saleIds))->delete();
            // delete the sales themselves
            SaleProduct::withTrashed()->whereIn('id', $saleIds)->forceDelete();
        }

        // Also delete any return_sale_items that reference this customer directly
        ReturnSaleItem::where('customerId', $id)->delete();
    }

    /**
     * Cascade delete all records related to a supplier id.
     * This performs permanent deletes across related tables.
     */
    private function cascadeDeleteSupplier(int $id){
        // Purchases belonging to this supplier
        $purchaseIds = PurchaseProduct::where('supplier', (string)$id)->pluck('id')->toArray();

        if(!empty($purchaseIds)){
            // invoice items referencing those purchases
            InvoiceItem::withTrashed()->whereIn('purchaseId', $purchaseIds)->forceDelete();
            // purchase returns that belong to those purchases
            PurchaseReturn::whereIn('purchaseId', $purchaseIds)->delete();
            // return purchase items linked to those purchases
            ReturnPurchaseItem::whereIn('purchaseId', $purchaseIds)->delete();
            // product stock rows tied to those purchases
            ProductStock::whereIn('purchaseId', $purchaseIds)->delete();
            // delete the purchases themselves
            PurchaseProduct::whereIn('id', $purchaseIds)->delete();
        }

        // Also delete any return_purchase_items that reference this supplier directly
        ReturnPurchaseItem::where('supplierId', $id)->delete();
    }

    // restore soft-deleted supplier
    public function restoreSupplier($id)
    {
        $sup = Supplier::onlyTrashed()->find($id);
        if(!$sup){
            Alert::error(__('common.notification_failed'),__('common.notification_supplier_not_found_or_not_deleted'));
            return back();
        }
        $sup->restore();
        Alert::success(__('common.notification_success'),__('common.notification_supplier_restored_successfully'));
        return back();
    }


    // balancesheet
    public function balancesheet(){
        return  view('customer&supplier.balancesheet');
    }

    
    // submit supplier by ajax
    public function createSupplier(Request $req){
        $workflow=app(\App\Services\PurchaseWorkflow::class);
        if($workflow->platform() && !$req->filled('businessId')){
            throw \Illuminate\Validation\ValidationException::withMessages([
                'businessId' => __('purchases.business_required'),
            ]);
        }
        $businessId=$workflow->resolveBusinessId($req->input('businessId'));
        // Validate incoming AJAX payload (keeps route unchanged)
        $payload = [
            'fullName'    => trim((string)$req->fullName),
            'email'       => strtolower(trim((string)$req->email)),
            'phoneNumber' => trim((string)$req->phoneNumber),
            'country'     => trim((string)$req->country),
            'state'       => trim((string)$req->state),
            'city'        => trim((string)$req->city),
            'area'        => trim((string)$req->area),
            'openingBalance' => (int)($req->openingBalance ?? 0),
        ];

        $validator = Validator::make($payload, [
            'fullName'    => ['required','string','min:2','max:150'],
            'email'       => ['nullable','email','max:190', function($attr,$value,$fail){
                                if(!empty($value)){
                                    $exists = Supplier::withTrashed()
                                        ->where('mail',$value)
                                        ->whereNull('deleted_at')
                                        ->exists();
                                    if($exists) $fail(__('common.email_taken'));
                                }
                            }],
            'phoneNumber' => ['nullable','string','min:6','max:25', function($attr,$value,$fail){
                                if(!empty($value)){
                                    $exists = Supplier::withTrashed()
                                        ->where('mobile',$value)
                                        ->whereNull('deleted_at')
                                        ->exists();
                                    if($exists) $fail('The mobile has already been taken.');
                                }
                            }],
            'country'     => ['nullable','string','max:100'],
            'state'       => ['nullable','string','max:100'],
            'city'        => ['nullable','string','max:100'],
            'area'        => ['nullable','string','max:150'],
            'openingBalance' => ['integer'],
        ]);

        if($validator->fails()){
            return [
                'data'    => '',
                'message' => __('common.api_validation_failed') . implode(', ', $validator->errors()->all()),
                'errors'  => $validator->errors()->toArray(),
            ];
        }

        $data = new Supplier();
        $data->businessId = $businessId;
        $data->name    = $payload['fullName'];
        $data->mail    = !empty($payload['email']) ? $payload['email'] : null;
        $data->mobile  = !empty($payload['phoneNumber']) ? $payload['phoneNumber'] : null;
        $data->country = !empty($payload['country']) ? $payload['country'] : null;
        $data->state   = !empty($payload['state']) ? $payload['state'] : null;
        $data->city    = !empty($payload['city']) ? $payload['city'] : null;
        $data->area    = !empty($payload['area']) ? $payload['area'] : null;
        $data->openingBalance = $payload['openingBalance'];
        
        $option = "";

        try{
            $saved = $data->save();
        }catch(\Throwable $e){
            return ['data'=> $option, 'message'=>__('common.api_error_there_was_an_error_please_try_again')];
        }

        if($saved){
            $getData = $workflow->scoped(Supplier::withoutGlobalScopes())->orderBy('id','DESC')->get();
            if(!empty($getData)){
                foreach($getData as $d){
                    $option .= '<option value="'.$d->id.'">'.$d->name.'</option>';
                }
            }
            return ['data'=> $option, 'message'=>__('common.api_success_form_successfully_submit')];
        }
        return ['data'=> $option, 'message'=>__('common.api_error_there_was_an_error_please_try_again')];
    }
}
