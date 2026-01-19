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
        $data = Customer::orderBy('id','DESC')->get();
        $trashed = Customer::onlyTrashed()->orderBy('id','DESC')->get();
        $openingTotal = Customer::sum('openingBalance');
        return view('customer&supplier.addCustomer',[
            'listItem'=>$data,
            'trashedList' => $trashed,
            'openingTotal' => $openingTotal,
        ]);
    }

    //save custmer
    public function saveCustomer(SaveCustomerRequest $req){
        $data = $req->profileId ? Customer::find($req->profileId) : new Customer();
        if($req->profileId && !$data):
            Alert::error('Failed!', 'Customer not found for update');
            return redirect()->route('addCustomer');
        endif;

        $data->name          = trim($req->fullName);
        $data->openingBalance= (int)$req->openingBalance;
        $data->mail          = strtolower(trim($req->mail));
        $data->mobile        = trim($req->mobile);
        $data->country       = trim($req->country);
        $data->state         = trim($req->state);
        $data->city          = trim($req->city);
        $data->area          = trim($req->area);

        if($data->save()):
            $message = $req->profileId ? 'Customer profile updated successfully' : 'Customer profile created successfully';
            Alert::success('Success!', $message);
            return redirect(route('addCustomer'));
        else:
            Alert::error('Failed!','Customer profile save failed');
            return back();
        endif;
    }

    //edit customer
     public function editCustomer($id){
        $data = Customer::find($id);
        return view('customer&supplier.addCustomer',['profile'=>$data]);
    }

    //delete customer
    public function delCustomer($id){
        $data = Customer::withTrashed()->find($id);
        if(!$data){
            Alert::error('Failed!','Customer not found');
            return back();
        }

        DB::beginTransaction();
        try{
            $this->cascadeDeleteCustomer($id);
            // finally delete customer record permanently
            $data->forceDelete();
            DB::commit();
            Alert::success('Deleted!','Customer profile and related records deleted successfully');
        }catch(\Throwable $e){
            DB::rollBack();
            Alert::error('Failed!','Failed to delete customer and related records');
        }
        return back();
    }

    // bulk delete customers
    public function bulkDeleteCustomer(Request $req){
        $ids = $req->input('selected', []);
        if(!is_array($ids) || count($ids) === 0){
            Alert::error('No selection','No customers selected for deletion');
            return back();
        }

        $clean = array_filter(array_map('intval', $ids));
        DB::beginTransaction();
        try{
            foreach($clean as $cid){
                $this->cascadeDeleteCustomer($cid);
                $cust = Customer::withTrashed()->find($cid);
                if($cust) $cust->forceDelete();
            }
            DB::commit();
            Alert::success('Deleted!','Selected customers and related records deleted successfully');
        }catch(\Throwable $e){
            DB::rollBack();
            Alert::error('Failed!','Failed to delete selected customers');
        }
        return back();
    }

     // submit customer by ajax
    public function createCustomer(Request $req){
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
                                        if($exists) $fail('The email has already been taken.');
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
                'message' => 'Validation failed: ' . implode(', ', $validator->errors()->all()),
                'errors'  => $validator->errors()->toArray(),
            ];
        }

        $data = new Customer();
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
                'message' => 'Error! Failed to create customer',
            ];
        }

        $option = '';
        if($saved){
            $getData = Customer::orderBy('id','DESC')->get();
            foreach($getData as $d){
                $option .= '<option value="'.$d->id.'">'.$d->name.'</option>';
            }
            return [
                'data'   => $option,
                'message'=>'Success! Customer created successfully'
            ];
        }
        return [
            'data'=> '',
            'message'=>'Error! Failed to create customer'
        ];
    }

    // restore soft-deleted customer
    public function restoreCustomer($id)
    {
        $cust = Customer::onlyTrashed()->find($id);
        if(!$cust){
            Alert::error('Failed!','Customer not found or not deleted');
            return back();
        }
        $cust->restore();
        Alert::success('Success!','Customer restored successfully');
        return back();
    }

    // add supplier
    public function addSupplier(){
        $data = Supplier::orderBy('id','DESC')->get();
        $trashed = Supplier::onlyTrashed()->orderBy('id','DESC')->get();
        $openingTotal = Supplier::sum('openingBalance');
        return view('customer&supplier.addSupplier',[
            'listItem'=>$data,
            'trashedList' => $trashed,
            'openingTotal' => $openingTotal,
        ]);
    }

    //save supplier
    public function saveSupplier(Request $req){
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
        $data = $isUpdate ? Supplier::find($req->profileId) : new Supplier();
        if($isUpdate && !$data){
            Alert::error('Failed!','Supplier not found');
            return back();
        }

        // Basic server-side sanitization and integer enforcement
        $data->name          = trim((string)$req->fullName);
        $data->openingBalance= (int)$req->openingBalance;
        $data->mail          = strtolower(trim((string)$req->mail));
        $data->mobile        = trim((string)$req->mobile);
        $data->country       = trim((string)$req->country);
        $data->state         = trim((string)$req->state);
        $data->city          = trim((string)$req->city);
        $data->area          = trim((string)$req->area);

        if($data->save()){
            $msg = $isUpdate ? 'Supplier profile updated successfully' : 'Supplier profile created successfully';
            Alert::success('Success!', $msg);
            return redirect(route('addSupplier'));
        }
        Alert::error('Failed!','Supplier profile save failed');
        return back();
    }

    //edit supplier
     public function editSupplier($id){
        $data = Supplier::find($id);
        return view('customer&supplier.addSupplier',['profile'=>$data]);
    }

    
    //delete supplier
    public function delSupplier($id){
        $data = Supplier::withTrashed()->find($id);
        if(!$data){
            Alert::error('Failed!','Supplier not found');
            return back();
        }

        DB::beginTransaction();
        try{
            $this->cascadeDeleteSupplier($id);
            $data->forceDelete();
            DB::commit();
            Alert::success('Deleted!','Supplier profile and related records deleted successfully');
        }catch(\Throwable $e){
            DB::rollBack();
            Alert::error('Failed!','Failed to delete supplier and related records');
        }
        return back();
    }

    // bulk delete suppliers
    public function bulkDeleteSupplier(Request $req){
        $ids = $req->input('selected', []);
        if(!is_array($ids) || count($ids) === 0){
            Alert::error('No selection','No suppliers selected for deletion');
            return back();
        }
        $clean = array_filter(array_map('intval', $ids));
        DB::beginTransaction();
        try{
            foreach($clean as $sid){
                $this->cascadeDeleteSupplier($sid);
                $sup = Supplier::withTrashed()->find($sid);
                if($sup) $sup->forceDelete();
            }
            DB::commit();
            Alert::success('Deleted!','Selected suppliers and related records deleted successfully');
        }catch(\Throwable $e){
            DB::rollBack();
            Alert::error('Failed!','Failed to delete selected suppliers');
        }
        return back();
    }

    /**
     * Cascade delete all records related to a customer id.
     * This performs permanent deletes across related tables.
     */
    private function cascadeDeleteCustomer(int $id){
        // Sales belonging to this customer
        $saleIds = SaleProduct::where('customerId', (string)$id)->pluck('id')->toArray();

        if(!empty($saleIds)){
            // invoice items referencing those sales
            InvoiceItem::whereIn('saleId', $saleIds)->delete();
            // return sale items referencing those sales
            ReturnSaleItem::whereIn('saleId', $saleIds)->delete();
            // sale returns (saleId column is text) - match as string
            SaleReturn::whereIn('saleId', array_map('strval',$saleIds))->delete();
            // delete the sales themselves
            SaleProduct::whereIn('id', $saleIds)->delete();
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
            InvoiceItem::whereIn('purchaseId', $purchaseIds)->delete();
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
            Alert::error('Failed!','Supplier not found or not deleted');
            return back();
        }
        $sup->restore();
        Alert::success('Success!','Supplier restored successfully');
        return back();
    }


    // balancesheet
    public function balancesheet(){
        return  view('customer&supplier.balancesheet');
    }

    
    // submit supplier by ajax
    public function createSupplier(Request $req){
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
                                    if($exists) $fail('The email has already been taken.');
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
                'message' => 'Validation failed: ' . implode(', ', $validator->errors()->all()),
                'errors'  => $validator->errors()->toArray(),
            ];
        }

        $data = new Supplier();
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
            return ['data'=> $option, 'message'=>'Error! There was an error. Please try again'];
        }

        if($saved){
            $getData = Supplier::orderBy('id','DESC')->get();
            if(!empty($getData)){
                foreach($getData as $d){
                    $option .= '<option value="'.$d->id.'">'.$d->name.'</option>';
                }
            }
            return ['data'=> $option, 'message'=>'Success! Form successfully submit'];
        }
        return ['data'=> $option, 'message'=>'Error! There was an error. Please try again'];
    }
}
