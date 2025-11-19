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
        $data = Customer::find($id);
        if($data):
            $data->delete();
            Alert::success('Deleted!','Customer profile deleted successfully');
            return back();
        else:
            Alert::error('Failed!','Customer not found');
            return back();
        endif;
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
            'mail'           => ['required','email','max:190', function($attr,$value,$fail){
                                    $exists = Customer::withTrashed()
                                        ->where('mail',$value)
                                        ->whereNull('deleted_at')
                                        ->exists();
                                    if($exists) $fail('The email has already been taken.');
                                }],
            'mobile'         => ['required','string','min:6','max:25', function($attr,$value,$fail){
                                    $exists = Customer::withTrashed()
                                        ->where('mobile',$value)
                                        ->whereNull('deleted_at')
                                        ->exists();
                                    if($exists) $fail('The mobile has already been taken.');
                                }],
            'country'        => ['required','string','max:100'],
            'state'          => ['required','string','max:100'],
            'city'           => ['required','string','max:100'],
            'area'           => ['required','string','max:150'],
            'openingBalance' => ['integer'],
        ]);

        if($validator->fails()){
            return [
                'data'    => '',
                'message' => 'Validation failed',
                'errors'  => $validator->errors()->toArray(),
            ];
        }

        $data = new Customer();
        $data->name            = $payload['fullName'];
        $data->mail            = $payload['mail'];
        $data->mobile          = $payload['mobile'];
        $data->country         = $payload['country'];
        $data->state           = $payload['state'];
        $data->city            = $payload['city'];
        $data->area            = $payload['area'];
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
                'required','email','max:190',
                Rule::unique('suppliers','mail')->ignore($req->profileId)->where(fn($q)=>$q->whereNull('deleted_at'))
            ],
            'mobile'        => [
                'required','string','min:6','max:25',
                Rule::unique('suppliers','mobile')->ignore($req->profileId)->where(fn($q)=>$q->whereNull('deleted_at'))
            ],
            'country'       => ['required','string','max:100'],
            'state'         => ['required','string','max:100'],
            'city'          => ['required','string','max:100'],
            'area'          => ['required','string','max:150'],
            'openingBalance'=> ['required','integer'],
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
        $data = Supplier::find($id);
        if(!empty($data)):
            $data->delete();
                Alert::success('Deleted!','Supplier profile deleted successfully');
                return back();
        else:
                Alert::error('Failed!','Supplier not found');
                return back();
        endif;
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
            'email'       => ['required','email','max:190', Rule::unique('suppliers','mail')->where(fn($q)=>$q->whereNull('deleted_at'))],
            'phoneNumber' => ['required','string','min:6','max:25', Rule::unique('suppliers','mobile')->where(fn($q)=>$q->whereNull('deleted_at'))],
            'country'     => ['required','string','max:100'],
            'state'       => ['required','string','max:100'],
            'city'        => ['required','string','max:100'],
            'area'        => ['required','string','max:150'],
            'openingBalance' => ['integer'],
        ]);

        if($validator->fails()){
            return [
                'data'    => '',
                'message' => 'Validation failed',
                'errors'  => $validator->errors()->toArray(),
            ];
        }

        $data = new Supplier();
        $data->name    = $payload['fullName'];
        $data->mail    = $payload['email'];
        $data->mobile  = $payload['phoneNumber'];
        $data->country = $payload['country'];
        $data->state   = $payload['state'];
        $data->city    = $payload['city'];
        $data->area    = $payload['area'];
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
