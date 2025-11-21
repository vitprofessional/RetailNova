<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ProductSaveRequest;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductUnit;
use Alert;
use App\Models\ProductStock;
use App\Http\Requests\BrandSaveRequest;
use App\Http\Requests\CategorySaveRequest;
use App\Http\Requests\ProductUnitSaveRequest;

class productController extends Controller
{
    public function addProduct(){
        $productUnit = ProductUnit::orderBy('id','DESC')->get();
        $category = Category::orderBy('id','DESC')->get();
        $brand = Brand::orderBy('id','DESC')->get();
        $data = Product::with(['brandModel','categoryModel','unitModel','stocks'])->orderBy('id','DESC')->get();

        return view('product.newProduct',[
            'listItem'=>$data,
            'brandList'=>$brand,
            'categoryList'=>$category,
            'productUnitList'=>$productUnit
        ]);
   }

   public function saveProduct(ProductSaveRequest $req){
       $validated = $req->validated();

        $data = $req->profileId ? Product::find($req->profileId) : new Product();
        if($req->profileId && !$data){
            Alert::error('Failed!','Product not found for update');
            return redirect()->route('addProduct');
        }

        $data->name     = trim($validated['name']);
        $data->brand    = (int)$validated['brand'];
        $data->category = (int)$validated['category'];
        $data->unitName = (int)$validated['unitName'];
        $data->quantity = $validated['quantity'] ?? 0;
        $data->details  = $validated['details'] ?? null;
        $data->barCode  = $validated['barCode'] ?? null;

        if($data->save()){
            $msg = $req->profileId ? 'Product updated successfully' : 'Product created successfully';
            Alert::success('Success!', $msg);
            return redirect(route('addProduct'));
        }
        Alert::error('Failed!','Product save failed');
        return back();
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
            // Authorization hook
            $this->authorize('delete', $data);
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
        $payload = [
            'fullName' => trim((string)$req->fullName),
            'brand'    => $req->brand,
            'category' => $req->category,
            'unitName' => $req->unitName,
            'quantity' => $req->quantity,
            'details'  => $req->details,
            'barCode'  => $req->barCode,
        ];

        $validator = \Validator::make($payload, [
            'fullName' => ['required','string','min:2','max:200'],
            'brand'    => ['required','integer','exists:brands,id'],
            'category' => ['required','integer','exists:categories,id'],
            'unitName' => ['required','integer','exists:product_units,id'],
            'quantity' => ['nullable','integer','min:0'],
            'details'  => ['nullable','string','max:1000'],
            'barCode'  => ['nullable','string','max:190'],
        ]);

        if($validator->fails()){
            return [
                'data' => '',
                'message' => 'Validation failed',
                'errors' => $validator->errors()->toArray(),
            ];
        }

        $data = new Product();
        $data->name     = $payload['fullName'];
        $data->brand    = (int)$payload['brand'];
        $data->category = (int)$payload['category'];
        $data->unitName = (int)$payload['unitName'];
        $data->quantity = (int)($payload['quantity'] ?? 0);
        $data->details  = $payload['details'];
        $data->barCode  = $payload['barCode'];
        $option = '';

        if($data->save()){
            $getData = Product::orderBy('id','DESC')->get();
            if($getData->count() > 0){
                $option .= '<option value="">Select</option>';
                foreach($getData as $d){
                    $option .= '<option value="'.$d->id.'">'.$d->name.'</option>';
                }
            }
            return ['data' => $option, 'message' => 'Success! Product created'];
        }
        return ['data' => $option, 'message' => 'Error! Failed to create product'];
    }

    //product list page
    public function productlist(){
                $productUnit = ProductUnit::orderBy('id','DESC')->get();
                $category = Category::orderBy('id','DESC')->get();
                $brand = Brand::orderBy('id','DESC')->get();
                $data = Product::with(['brandModel','categoryModel','unitModel','stocks'])->orderBy('id','DESC')->get();
                return view('product.productList',[
                        'listItem'=>$data,
                        'brandList'=>$brand,
                        'categoryList'=>$category,
                        'productUnitList'=>$productUnit
                ]);
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
        $productStock = ProductStock::join('products','products.id','product_stocks.productId')->leftJoin('purchase_products','purchase_products.id','product_stocks.purchaseId')->leftJoin('suppliers','suppliers.id','purchase_products.supplier')->select(
            'product_stocks.id as stockId',
            'products.id as productId',
            'products.name as productName',
            'purchase_products.invoice as invoiceNo',
            'purchase_products.buyPrice as buyPrice',
            'purchase_products.salePriceExVat as salePriceExVat',
            'purchase_products.salePriceInVat as salePriceInVat',
            'purchase_products.disAmount as discount',
            'product_stocks.currentStock',
            'suppliers.name as supplierName'
        )->orderBy('product_stocks.id','DESC')->get();
        return view('product.stockProduct',['productStockList'=>$productStock]);
   }
    
    //addBrand
    public function addBrand(){
      $data = Brand::orderBy('id','DESC')->get();
      return view('product.brand',['listItem'=>$data]);
   }
    //save Brand
    public function saveBrand(BrandSaveRequest $req){
        if($req->profileId):
            $data = Brand::find($req->profileId);
        else:
            $data = new Brand();
        endif;
            $data->name          = $req->validated()['name'];

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
            $this->authorize('delete', $data);
            $data->delete();
                Alert::success('Success!','Brand delete successfully');
                return back();
        else:
                Alert::error('Failed!','Brand delete failed');
                return back();
        endif;
    }


    // submit Brand by ajax
        public function createBrand(BrandSaveRequest $req){
            $data = new Brand();
            $data->name     = $req->validated()['name'];
            
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
    public function saveCategory(CategorySaveRequest $req){
        if($req->profileId):
            $data = Category::find($req->profileId);
        else:
            $data = new Category();
        endif;
            $data->name          = $req->validated()['name'];

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
            $this->authorize('delete', $data);
            $data->delete();
                Alert::success('Success!','Category delete successfully');
                return back();
        else:
                Alert::error('Failed!','Category delete failed');
                return back();
        endif;
    }

    // submit Category by ajax
        public function createCategory(CategorySaveRequest $req){
            $data = new Category();
            $data->name     = $req->validated()['name'];
            
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
    public function saveProductUnit(ProductUnitSaveRequest $req){
        if($req->profileId):
            $data = ProductUnit::find($req->profileId);
        else:
            $data = new ProductUnit();
        endif;
            $data->name          = $req->validated()['name'];

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
            $this->authorize('delete', $data);
            $data->delete();
                Alert::success('Success!','Product unit delete successfully');
                return back();
        else:
                Alert::error('Failed!','Product unit delete failed');
                return back();
        endif;
    }

    // submit ProductUnit by ajax
        public function createProductUnit(ProductUnitSaveRequest $req){
            $data = new ProductUnit();
            $data->name     = $req->validated()['name'];
            
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
