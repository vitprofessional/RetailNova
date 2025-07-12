<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\ProvideService;
use App\Models\Customer;
use Alert;

class serviceController extends Controller
{

//add service
  Public function addServiceName (){
      $data = Service::orderBy('id','DESC')->get();
    return view('service.addService',['listItem'=>$data]);

  }
  //save service 
  Public function saveService(Request $req){
    if($req->profileId):
      $data = Service::find($req->profileId);
    else:
      $data = new Service();
    endif;

    $data->serviceName = $req->serviceName;
    $data->rate = $req->rate;


    if($data->save()):
        Alert::success('Success!','Service profile created successfully');
        return redirect(route('addServiceName'));
    else:
        Alert::error('Failed!','Service profile creation failed');
        return back();
    endif;
  }

  //edit service
  public function editService($id){
    $data = Service::find($id);
    return view('service.addService',['profile'=>$data]);
  }

   //delete Service
    public function delService($id){
        $data = Service::find($id);
        if($data->save()):
            $data->delete();
            Alert::success('Success!','Service delete successfully');
            return redirect(route('addServiceName'));
        else:
            Alert::error('Failed!','Service delete failed');
            return back();
        endif;
    }


  //add provideService
  Public function provideService(){
    $service = Service::orderBy('id','DESC')->get();
    $customer = Customer::orderBy('id','DESC')->get();
    return view('service.provideService',['customerList'=>$customer,'serviceList'=>$service]);
  }

  //save provideService service 
  Public function saveProvideService(Request $req){
    if($req->profileId):
      $data = ProvideService::find($req->profileId);
    else:
      $data = new ProvideService();
    endif;

    $data->customerName = $req->customerName;
    $data->serviceName  = $req->serviceName;
    $data->amount       = $req->amount;
    $data->note         = $req->note;

    if($data->save()):
        Alert::success('Success!','Service profile created successfully');
        return redirect(route('provideService'));
    else:
        Alert::error('Failed!','Service profile creation failed');
        return back();
    endif;
  }

  
  //list provideService
  Public function serviceProvideList(){
    return view('service.serviceList');
  }
}
