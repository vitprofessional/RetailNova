<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductUnit;
use Alert;

class productController extends Controller
{
    public function addProduct(){
        $productUnit = ProductUnit::orderBy('id','DESC')->get();
        $category = Category::orderBy('id','DESC')->get();
        $brand = Brand::orderBy('id','DESC')->get();
        $data = Product::orderBy('id','DESC')->get();

        return view('product.newProduct',['listItem'=>$data,'brandList'=>$brand,'categoryList'=>$category,'productUnitList'=>$productUnit]);
   }

   public function saveProduct(Request $req){
        if($req->profileId):
            $data = Product::find($req->profileId);
        else:
            $data = new Product();
        endif;
            $data->name          = $req->name;
            $data->brand         = $req->brand;
            $data->category      = $req->category;
            $data->unitName      = $req->unitName;
            $data->quantity      = $req->quantity;
            $data->details       = $req->details;
            $data->barCode       = $req->barCode;

            if($data->save()):
                Alert::success('Success!','Product created successfully');
                return redirect(route('addProduct'));
            else:
                Alert::error('Failed!','Product creation failed');
                return back();
            endif;
    }

    //edit product
     public function editProduct($id){
        $productUnit = ProductUnit::orderBy('id','DESC')->get();
        $category = Category::orderBy('id','DESC')->get();
        $brand = Brand::orderBy('id','DESC')->get();
        $data = product::find($id);
        return view('product.newProduct',['profile'=>$data,'brandList'=>$brand,'categoryList'=>$category,'productUnitList'=>$productUnit]);
    }

    //delete product
    public function delProduct($id){
        $data = Product::find($id);
        if(!empty($data)):
            $data->delete();
                Alert::success('Success!','Product delete successfully');
                return back();
        else:
                Alert::error('Failed!','Product delete failed');
                return back();
        endif;
    }

    
    // submit product by ajax
    public function createProduct(Request $req){
            $data = new Product();
            $data->name          = $req->fullName;
            $data->brand         = $req->brand;
            $data->category      = $req->category;
            $data->unitName      = $req->unitName;
            $data->quantity      = $req->quantity;
            $data->details       = $req->details;
            $data->barCode       = $req->barCode;
            $option="";

            if($data->save()):
                $getData = Product::orderBy('id','DESC')->get();
                if(!empty($getData)):
                    $option .='<option value="">Select</option>';
                    foreach($getData as $d):
                        $option .='<option value="'.$d->id.'">'.$d->name.'</option>';
                    endforeach;
                endif;
                
                return ['data' => $option, 'message'=>'Success ! Form successfully subjmit.'];
            else:
                return ['data' => $option, 'message'=>'Error ! There is an error. Please try agin.'];
            endif;
    }

    //product list page
    public function productlist(){
        $productUnit = ProductUnit::orderBy('id','DESC')->get();
        $category = Category::orderBy('id','DESC')->get();
        $brand = Brand::orderBy('id','DESC')->get();
        $data = Product::orderBy('id','DESC')->get();
      return view('product.productList',['listItem'=>$data,'brandList'=>$brand,'categoryList'=>$category,'productUnitList'=>$productUnit]);
    }

    

    //add bamage product
    public function damageProduct(){
        return view('product.damageProduct');
   }
    //add bamage product list
    public function damageProductList(){
        return view('product.damageProductList');
   }


    
    //stock product
    public function stockProduct(){
      return view('product.stockProduct');
   }
    
    //addBrand
    public function addBrand(){
      $data = Brand::orderBy('id','DESC')->get();
      return view('product.brand',['listItem'=>$data]);
   }
    //save Brand
    public function saveBrand(Request $req){
        if($req->profileId):
            $data = Brand::find($req->profileId);
        else:
            $data = new Brand();
        endif;
            $data->name          = $req->name;

            if($data->save()):
                Alert::success('Success!','Brand created successfully');
                return redirect(route('addBrand'));
            else:
                Alert::error('Failed!','Brand creation failed');
                return back();
            endif;
    }


    //edit Brand
     public function editBrand($id){
        $data = Brand::find($id);
        return view('product.brand',['profile'=>$data]);
    }

    //delete Brand
    public function delBrand($id){
        $data = Brand::find($id);
        if(!empty($data)):
            $data->delete();
            $data->delete();
                Alert::success('Success!','Brand delete successfully');
                return back();
        else:
                Alert::error('Failed!','Brand delete failed');
                return back();
        endif;
    }


    // submit Brand by ajax
    public function createBrand(Request $req){
            $data = new Brand();
            $data->name     = $req->name;
            
            $option="";

            if($data->save()):
                $getData = Brand::orderBy('id','DESC')->get();
                if(!empty($getData)):
                    $option .='<option value="">Select</option>';
                    foreach($getData as $d):
                        $option .='<option value="'.$d->id.'">'.$d->name.'</option>';
                    endforeach;
                endif;
                
                return ['data' => $option, 'message'=>'Success ! Form successfully subjmit.'];
            else:
                return ['data' => $option, 'message'=>'Error ! There is an error. Please try agin.'];
            endif;
    }


    //addCategory
    public function addCategory(){
      $data = Category::orderBy('id','DESC')->get();
      return view('product.category',['listItem'=>$data]);
   }

    //save Category
    public function saveCategory(Request $req){
        if($req->profileId):
            $data = Category::find($req->profileId);
        else:
            $data = new Category();
        endif;
            $data->name          = $req->name;

            if($data->save()):
                Alert::success('Success!','Category created successfully');
                return redirect(route('addCategory'));
            else:
                Alert::error('Failed!','Category creation failed');
                return back();
            endif;
    }


    //edit Category
     public function editCategory($id){
        $data = Category::find($id);
        return view('product.category',['profile'=>$data]);
    }

    //delete Category
    public function delCategory($id){
        $data = Category::find($id);
        if(!empty($data)):
            $data->delete();
                Alert::success('Success!','Category delete successfully');
                return back();
        else:
                Alert::error('Failed!','Category delete failed');
                return back();
        endif;
    }

    // submit Category by ajax
    public function createCategory(Request $req){
            $data = new Category();
            $data->name     = $req->name;
            
            $option="";

            if($data->save()):
                $getData = Category::orderBy('id','DESC')->get();
                if(!empty($getData)):
                    $option .='<option value="">Select</option>';
                    foreach($getData as $d):
                        $option .='<option value="'.$d->id.'">'.$d->name.'</option>';
                    endforeach;
                endif;
                
                return ['data' => $option, 'message'=>'Success ! Form successfully subjmit.'];
            else:
                return ['data' => $option, 'message'=>'Error ! There is an error. Please try agin.'];
            endif;
    }


    //addProductUnit
    public function addProductUnit(){
      $data = ProductUnit::orderBy('id','DESC')->get();
      return view('product.productUnit',['listItem'=>$data]);
   }
    //save ProductUnit
    public function saveProductUnit(Request $req){
        if($req->profileId):
            $data = ProductUnit::find($req->profileId);
        else:
            $data = new ProductUnit();
        endif;
            $data->name          = $req->name;

            if($data->save()):
                Alert::success('Success!','Product unit created successfully');
                return redirect(route('addProductUnit'));
            else:
                Alert::error('Failed!','Product unit creation failed');
                return back();
            endif;
    }


    //edit ProductUnit
     public function editProductUnit($id){
        $data = ProductUnit::find($id);
        return view('product.productUnit',['profile'=>$data]);
    }

    //delete ProductUnit
    public function delProductUnit($id){
        $data = ProductUnit::find($id);
        if(!empty($data)):
            $data->delete();
                Alert::success('Success!','Product unit delete successfully');
                return back();
        else:
                Alert::error('Failed!','Product unit delete failed');
                return back();
        endif;
    }

    // submit ProductUnit by ajax
    public function createProductUnit(Request $req){
            $data = new ProductUnit();
            $data->name     = $req->name;
            
            $option="";

            if($data->save()):
                $getData = ProductUnit::orderBy('id','DESC')->get();
                if(!empty($getData)):
                    $option .='<option value="">Select</option>';
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
