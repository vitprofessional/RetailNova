<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\balancesheet;
use Alert;

class coustomerSupplier extends Controller
{
    // add coustomer
    public function addCustomer(){
        $data = Customer::orderBy('id','DESC')->get();
        return view('customer&supplier.addCustomer',['listItem'=>$data]);
    }

    //save custmer
    public function saveCustomer(Request $req){
        if($req->profileId):
            $data = Customer::find($req->profileId);
        else:
            $data = new Customer();
        endif;
            $data->name          = $req->fullName;
            $data->accReceivable = $req->accReceivable;
            $data->accPayable    = $req->accPayable;
            $data->mail          = $req->mail;
            $data->mobile        = $req->mobile;
            $data->country       = $req->country;
            $data->state         = $req->state;
            $data->city          = $req->city;
            $data->area          = $req->area;

            if($data->save()):
                Alert::success('Success!','Customer profile created successfully');
                return redirect(route('addCustomer'));
            else:
                Alert::error('Failed!','Customer profile creation failed');
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
        if(!empty($data)):
            $data->delete();
                Alert::success('Success!','Customer profile created successfully');
                return back();
        else:
                Alert::error('Failed!','Customer profile creation failed');
                return back();
        endif;
    }

     // submit customer by ajax
    public function createCustomer(Request $req){
        $data = new Customer();

            $data->name          = $req->fullName;
            $data->mail          = $req->mail;
            $data->mobile        = $req->mobile;
            $data->country       = $req->country;
            $data->state         = $req->state;
            $data->city          = $req->city;
            $data->area          = $req->area;
        
        $option = "";

        if($data->save()):
            $getData = Customer::orderBy('id','DESC')->get();
            if(!empty($getData)):
                foreach($getData as $d):
                    $option .= '<option value="'.$d->id.'">'.$d->name.'</option>';
                endforeach;
            endif;

            return ['data'=> $option, 'message'=>'Success! Form successfully submit'];
        else:
            return ['data'=> $option, 'message'=>'Error !  There was an error. Please try agin'];
        endif;
    }

    // add supplier
    public function addSupplier(){
        $data = Supplier::orderBy('id','DESC')->get();
        return view('customer&supplier.addSupplier',['listItem'=>$data]);
    }

    //save supplier
    public function saveSupplier(Request $req){
        if($req->profileId):
            $data = Supplier::find($req->profileId);
        else:
            $data = new Supplier();
        endif;
            $data->name          = $req->fullName;
            $data->accReceivable = $req->accReceivable;
            $data->accPayable    = $req->accPayable;
            $data->mail          = $req->mail;
            $data->mobile        = $req->mobile;
            $data->country       = $req->country;
            $data->state         = $req->state;
            $data->city          = $req->city;
            $data->area          = $req->area;

            if($data->save()):
                Alert::success('Success!','Supplier profile created successfully');
                return redirect(route('addSupplier'));
            else:
                Alert::error('Failed!','Supplier profile creation failed');
                return back();
            endif;
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
                Alert::success('Success!','Supplier profile created successfully');
                return back();
        else:
                Alert::error('Failed!','Supplier profile creation failed');
                return back();
        endif;
    }


    // balancesheet
    public function balancesheet(){
        return  view('customer&supplier.balancesheet');
    }

    
    // submit supplier by ajax
    public function createSupplier(Request $req){
        $data = new Supplier();

        $data->name          = $req->fullName;
        $data->mail          = $req->email;
        $data->mobile        = $req->phoneNumber;
        $data->country       = $req->country;
        $data->state         = $req->state;
        $data->city          = $req->city;
        $data->area          = $req->area;
        
        $option = "";

        if($data->save()):
            $getData = Supplier::orderBy('id','DESC')->get();
            if(!empty($getData)):
                foreach($getData as $d):
                    $option .= '<option value="'.$d->id.'">'.$d->name.'</option>';
                endforeach;
            endif;

            return ['data'=> $option, 'message'=>'Success! Form successfully submit'];
        else:
            return ['data'=> $option, 'message'=>'Error !  There was an error. Please try agin'];
        endif;
    }
}
