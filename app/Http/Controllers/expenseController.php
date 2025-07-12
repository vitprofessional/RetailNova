<?php

namespace App\Http\Controllers;
use App\Models\Expense;
use Alert;

use Illuminate\Http\Request;

class expenseController extends Controller
{

    //addExpense
    public function addExpense(){
      $data = Expense::orderBy('id','DESC')->get();
      return view('expense.expensetype',['listItem'=>$data]);
   }

    //save Expense
    public function saveExpense(Request $req){
        if($req->profileId):
            $data = Expense::find($req->profileId);
        else:
            $data = new Expense();
        endif;
            $data->name          = $req->name;

            if($data->save()):
                Alert::success('Success!','Expense created successfully');
                return redirect(route('addExpense'));
            else:
                Alert::error('Failed!','Expense creation failed');
                return back();
            endif;
    }


    //edit Expense
     public function editExpense($id){
        $data = Expense::find($id);
        return view('expense.expensetype',['profile'=>$data]);
    }

    //delete Expense
    public function delExpense($id){
        $data = Expense::find($id);
        if(!empty($data)):
            $data->delete();
             Alert::success('Success!','Expense created successfully');
                return redirect(route('addExpense'));
        else:
           lert::error('Failed!','Expense creation failed');
                return back();
        endif;
    }


     Public function expense (){
      $data = Expense::orderBy('id','DESC')->get();
        return view('expense.expense',['expenseList'=>$data]);

    }

    

    // submit Expense by ajax
    public function createExpense(Request $req){
            $data = new Expense();
            $data->name     = $req->name;
            
            $option="";

            if($data->save()):
                $getData = Expense::orderBy('id','DESC')->get();
                if(!empty($getData)):
                    foreach($getData as $d):
                        $option .='<option value="'.$d->id.'">'.$d->name.'</option>';
                    endforeach;
                endif;
                
                return ['data' => $option, 'message'=>'Success ! Form successfully subjmit.'];
            else:
                return ['data' => $option, 'message'=>'Error ! There is an error. Please try agin.'];
            endif;
    }



}
