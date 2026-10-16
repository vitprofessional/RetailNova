<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ProductSaveRequest;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductUnit;
use Alert;
use Illuminate\Support\Facades\Auth;
use App\Models\ProductStock;
use App\Models\DamageProduct;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\BrandSaveRequest;
use App\Http\Requests\CategorySaveRequest;
use App\Http\Requests\ProductUnitSaveRequest;

class productController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $actor = Auth::guard('admin')->user();
            return $next($request);
        });
    }
    public function addProduct(){
        $workflow = app(\App\Services\PurchaseWorkflow::class);
        $productUnit = ProductUnit::orderBy('id','DESC')->get();
        $category = Category::orderBy('id','DESC')->get();
        $brand = Brand::orderBy('id','DESC')->get();
        $businesses = \App\Models\BusinessSetup::when(!$workflow->platform(),fn($q)=>$q->whereKey($workflow->resolveBusinessId()))->orderBy('id','asc')->get();

        return view('product.newProduct',[
            'brandList'=>$brand,
            'categoryList'=>$category,
            'productUnitList'=>$productUnit,
            'businesses' => $businesses,
            'businessSelectionAllowed' => $workflow->platform(),
        ]);
   }

   public function saveProduct(ProductSaveRequest $req){
       $validated = $req->validated();
        $workflow = app(\App\Services\PurchaseWorkflow::class);
        $data = $req->profileId ? $workflow->scoped(Product::withoutGlobalScopes())->findOrFail($req->profileId) : new Product();
        $requestedBusinessId = $req->input('businessId');
        if ($req->profileId && ($requestedBusinessId === null || $requestedBusinessId === '')) {
            $requestedBusinessId = $data->businessId;
        }
        $businessId = $workflow->resolveBusinessId($requestedBusinessId);
        abort_if($req->profileId && (int)$data->businessId !== $businessId, 403, 'Products cannot be moved between businesses through edit.');

        $data->name     = trim($validated['name']);
        $data->brand    = isset($validated['brand']) ? (int)$validated['brand'] : null;
        $data->category = isset($validated['category']) ? (int)$validated['category'] : null;
        $data->unitName = isset($validated['unitName']) ? (int)$validated['unitName'] : null;
        $data->quantity = $validated['quantity'] ?? 0;
        $data->details  = $validated['details'] ?? null;
        $data->barCode  = $validated['barCode'] ?? null;

        $data->businessId = $businessId;

        if($data->save()){
            $msg = $req->profileId ? __('common.product_updated') : __('common.product_created');
            Alert::success(__('common.notification_success'), $msg);
            return redirect(route('addProduct'));
        }
        Alert::error(__('common.notification_failed'),__('common.notification_product_save_failed'));
        return back();
    }

    //edit product
     public function editProduct($id){
        $workflow = app(\App\Services\PurchaseWorkflow::class);
        $productUnit = ProductUnit::orderBy('id','DESC')->get();
        $category = Category::orderBy('id','DESC')->get();
        $brand = Brand::orderBy('id','DESC')->get();
        $data = $workflow->scoped(Product::withoutGlobalScopes())->findOrFail($id);
        $businesses = \App\Models\BusinessSetup::when(!$workflow->platform(),fn($q)=>$q->whereKey($data->businessId))->orderBy('id')->get();
        return view('product.newProduct',['profile'=>$data,'brandList'=>$brand,'categoryList'=>$category,
            'productUnitList'=>$productUnit,'businesses'=>$businesses,'businessSelectionAllowed'=>$workflow->platform()]);
    }

    //delete product
    public function delProduct($id){
        $data = app(\App\Services\PurchaseWorkflow::class)->scoped(Product::withoutGlobalScopes())->find($id);
        if(!empty($data)):
            // Authorization hook
            $this->authorize('delete', $data);
            $data->delete();
                Alert::success(__('common.notification_success'),__('common.notification_product_delete_successfully'));
                return back();
        else:
                Alert::error(__('common.notification_failed'),__('common.notification_product_delete_failed'));
                return back();
        endif;
    }

    
    // submit product by ajax
    public function createProduct(Request $req){
        $workflow = app(\App\Services\PurchaseWorkflow::class);
        $businessId = $workflow->resolveBusinessId($req->input('businessId'));
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
            return response()->json([
                'data' => '',
                'message' => __('common.api_validation_failed'),
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $data = new Product();
        $data->name     = $payload['fullName'];
        $data->brand    = (int)$payload['brand'];
        $data->category = (int)$payload['category'];
        $data->unitName = (int)$payload['unitName'];
        $data->quantity = (int)($payload['quantity'] ?? 0);
        $data->details  = $payload['details'];
        $data->barCode  = $payload['barCode'];
        $data->businessId = $businessId;
        $option = '';

        if($data->save()){
            $getData = $workflow->scoped(Product::withoutGlobalScopes())->orderBy('id','DESC')->get();
            if($getData->count() > 0){
                $option .= '<option value="">Select</option>';
                foreach($getData as $d){
                    $option .= '<option value="'.e($d->id).'">'.e($d->name).'</option>';
                }
            }
            return ['data' => $option, 'message' => __('common.api_success_product_created')];
        }
        return ['data' => $option, 'message' => __('common.api_error_failed_to_create_product')];
    }

    //product list page
    public function productlist(){
                $workflow = app(\App\Services\PurchaseWorkflow::class);
                $productUnit = ProductUnit::orderBy('id','DESC')->get();
                $category = Category::orderBy('id','DESC')->get();
                $brand = Brand::orderBy('id','DESC')->get();
                $data = $workflow->scoped(Product::withoutGlobalScopes()->with(['brandModel','categoryModel','unitModel','stocks']))->orderBy('id','DESC')->get();
                $stockSummary = ['total' => $data->count(), 'in_stock' => 0, 'low_stock' => 0, 'out_of_stock' => 0];
                foreach ($data as $product) {
                    if ($product->is_out_of_stock) {
                        $stockSummary['out_of_stock']++;
                    } elseif ($product->is_low_stock) {
                        $stockSummary['low_stock']++;
                    } else {
                        $stockSummary['in_stock']++;
                    }
                }
                return view('product.productList',[
                        'listItem'=>$data,
                        'stockSummary'=>$stockSummary,
                        'brandList'=>$brand,
                        'categoryList'=>$category,
                        'productUnitList'=>$productUnit
                ]);
    }

    /**
     * Bulk delete products (simple hard delete, no stock reversion beyond deleting related ProductStock rows).
     */
    public function bulkDeleteProducts(Request $req){
        $ids = (array)$req->input('ids', $req->input('selected', []));
        if(empty($ids)){ return back()->with('error',__('common.notification_no_products_selected')); }
        try{
            DB::transaction(function() use ($ids){
                ProductStock::whereIn('productId',$ids)->delete();
                Product::whereIn('id',$ids)->delete();
            });
            Alert::success(__('common.notification_deleted'),__('common.notification_selected_products_deleted'));
        }catch(\Exception $e){
            \Log::error('bulkDeleteProducts failed: '.$e->getMessage());
            Alert::error(__('common.notification_error'),__('common.notification_failed_to_delete_selected_products'));
        }
        return back();
    }

    public function bulkDeleteBrands(Request $req){
        $ids = (array)$req->input('ids', $req->input('selected', []));
        if(empty($ids)){ return back()->with('error',__('common.notification_no_brands_selected')); }
        try{
            Brand::whereIn('id',$ids)->delete();
            Alert::success(__('common.notification_deleted'),__('common.notification_selected_brands_deleted'));
        }catch(\Exception $e){
            \Log::error('bulkDeleteBrands failed: '.$e->getMessage());
            Alert::error(__('common.notification_error'),__('common.notification_failed_to_delete_selected_brands'));
        }
        return back();
    }

    public function bulkDeleteCategories(Request $req){
        $ids = (array)$req->input('ids', $req->input('selected', []));
        if(empty($ids)){ return back()->with('error',__('common.notification_no_categories_selected')); }
        try{
            Category::whereIn('id',$ids)->delete();
            Alert::success(__('common.notification_deleted'),__('common.notification_selected_categories_deleted'));
        }catch(\Exception $e){
            \Log::error('bulkDeleteCategories failed: '.$e->getMessage());
            Alert::error(__('common.notification_error'),__('common.notification_failed_to_delete_selected_categories'));
        }
        return back();
    }

    public function bulkDeleteUnits(Request $req){
        $ids = (array)$req->input('ids', $req->input('selected', []));
        if(empty($ids)){ return back()->with('error',__('common.notification_no_units_selected')); }
        try{
            ProductUnit::whereIn('id',$ids)->delete();
            Alert::success(__('common.notification_deleted'),__('common.notification_selected_units_deleted'));
        }catch(\Exception $e){
            \Log::error('bulkDeleteUnits failed: '.$e->getMessage());
            Alert::error(__('common.notification_error'),__('common.notification_failed_to_delete_selected_units'));
        }
        return back();
    }

    public function bulkDeleteDamageProducts(Request $req){
        $ids = (array)$req->input('ids', $req->input('selected', []));
        if(empty($ids)){ return back()->with('error',__('common.notification_no_damage_records_selected')); }
        try{
            DamageProduct::whereIn('id',$ids)->delete();
            Alert::success(__('common.notification_deleted'),__('common.notification_selected_damage_records_deleted'));
        }catch(\Exception $e){
            \Log::error('bulkDeleteDamageProducts failed: '.$e->getMessage());
            Alert::error(__('common.notification_error'),__('common.notification_failed_to_delete_selected_damage_records'));
        }
        return back();
    }

    

    //add bamage product
    public function damageProduct(){
        // provide products to the damage product form so the select is populated
        $productList = Product::orderBy('name','ASC')->get();
        return view('product.damageProduct', [
            'productList' => $productList
        ]);
   }
    //add bamage product list
    public function damageProductList(){
        // Fetch damage records with product and admin relations for the list view
        $damageList = DamageProduct::with(['product','admin'])->orderBy('id','DESC')->get();
        // Provide product list for client-side filtering
        $productList = Product::orderBy('name','ASC')->get(['id','name']);
        return view('product.damageProductList', [
            'damageList' => $damageList,
            'productList' => $productList
        ]);
   }

    // handle saving damage product report and adjust stock (FIFO)
    public function damageProductSave(Request $req){
        $validator = \Validator::make($req->all(), [
            'purchaseDate' => ['nullable','date'],
            'refData'      => ['nullable','string','max:190'],
            'productName'  => ['required','integer','exists:products,id'],
            'qty'          => ['required','integer','min:1'],
            'buyingPrice'  => ['nullable','numeric'],
            'salingPriceWithoutVat' => ['nullable','numeric'],
        ]);

        if($validator->fails()){
            Alert::error(__('common.notification_validation_failed'), implode(', ', $validator->errors()->all()));
            return back()->withInput();
        }

        $productId = (int)$req->productName;
        $qty = (int)$req->qty;

        $totalStock = ProductStock::where('productId', $productId)->sum('currentStock');
        if($qty > $totalStock){
            Alert::error(__('common.notification_insufficient_stock'),__('inventory.damage_exceeds_stock', ['qty'=>$qty,'stock'=>$totalStock]));
            return back()->withInput();
        }

        try{
            DB::transaction(function() use ($req, $productId, $qty){
                $remaining = $qty;
                // Deduct FIFO from ProductStock rows
                $stocks = ProductStock::where('productId', $productId)->where('currentStock','>',0)->orderBy('id','asc')->lockForUpdate()->get();
                foreach($stocks as $s){
                    if($remaining <= 0) break;
                    $take = min((int)$s->currentStock, $remaining);
                    $s->currentStock = max(0, (int)$s->currentStock - $take);
                    $s->save();
                    $remaining -= $take;
                }

                $buy = $req->get('buyingPrice');
                $sale = $req->get('salingPriceWithoutVat');
                // Prefer sale price for total if provided, otherwise use buy price
                $unitPrice = is_numeric($sale) ? $sale : (is_numeric($buy) ? $buy : 0);
                $total = $unitPrice * $qty;

                $damage = new DamageProduct();
                $damage->reference = $req->refData;
                $damage->date = $req->purchaseDate ?: now();
                $damage->product_id = $productId;
                $damage->qty = $qty;
                $damage->buy_price = $buy;
                $damage->sale_price = $sale;
                $damage->total = $total;
                $damage->admin_id = auth('admin')->id() ?? null;
                $damage->save();
            });
            Alert::success(__('common.notification_success'),__('common.notification_damage_record_saved_and_stock_updated'));
            return redirect()->route('damageProductList');
        }catch(\Exception $e){
            \Log::error('damageProductSave failed: '.$e->getMessage());
            Alert::error(__('common.notification_error'),__('common.notification_failed_to_save_damage_record'));
            return back()->withInput();
        }
    }

    // Show a single damage record
    public function damageProductView($id){
        $damage = DamageProduct::with(['product','admin'])->find($id);
        if(!$damage){
            Alert::error(__('common.notification_not_found'),__('common.notification_damage_record_not_found'));
            return redirect()->route('damageProductList');
        }
        return view('product.damageProductView',['damage'=>$damage]);
    }

    // Printable damage record
    public function damageProductPrint($id){
        $damage = DamageProduct::with(['product','admin'])->find($id);
        if(!$damage){
            Alert::error(__('common.notification_not_found'),__('common.notification_damage_record_not_found'));
            return redirect()->route('damageProductList');
        }
        return view('product.damageProductPrint',['damage'=>$damage]);
    }

    // Delete damage record and restore stock by creating a stock entry
    public function damageProductDelete($id){
        $d = DamageProduct::find($id);
        if(!$d){
            Alert::error(__('common.notification_not_found'),__('common.notification_damage_record_not_found'));
            return back();
        }
        try{
            DB::transaction(function() use ($d){
                $workflow = app(\App\Services\PurchaseWorkflow::class);
                $product = $workflow->scoped(Product::withoutGlobalScopes())->findOrFail($d->product_id);
                $businessId = filter_var($product->businessId, FILTER_VALIDATE_INT);
                abort_if($businessId === false || (int)$businessId <= 0
                    || !\App\Models\BusinessSetup::whereKey((int)$businessId)->exists(), 422,
                    'Product ownership is incomplete.');
                // restore stock by adding a new ProductStock record
                ProductStock::create([
                    'purchaseId' => null,
                    'productId' => $d->product_id,
                    'businessId' => (int)$businessId,
                    'currentStock' => (int)($d->qty ?? 0)
                ]);
                $d->delete();
            });
            Alert::success(__('common.notification_deleted'),__('common.notification_damage_record_removed_and_stock_restored'));
            return redirect()->route('damageProductList');
        }catch(\Exception $e){
            \Log::error('damageProductDelete failed: '.$e->getMessage());
            Alert::error(__('common.notification_error'),__('common.notification_failed_to_delete_damage_record'));
            return back();
        }
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
                Alert::success(__('common.notification_success'),__('common.notification_brand_created_successfully'));
                return redirect(route('addBrand'));
            else:
                Alert::error(__('common.notification_failed'),__('common.notification_brand_creation_failed'));
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
                Alert::success(__('common.notification_success'),__('common.notification_brand_delete_successfully'));
                return back();
        else:
                Alert::error(__('common.notification_failed'),__('common.notification_brand_delete_failed'));
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
                
                return ['data' => $option, 'message'=>__('common.api_success_form_successfully_subjmit')];
            else:
                return ['data' => $option, 'message'=>__('common.api_error_there_is_an_error_please_try_agin')];
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
                Alert::success(__('common.notification_success'),__('common.notification_category_created_successfully'));
                return redirect(route('addCategory'));
            else:
                Alert::error(__('common.notification_failed'),__('common.notification_category_creation_failed'));
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
                Alert::success(__('common.notification_success'),__('common.notification_category_delete_successfully'));
                return back();
        else:
                Alert::error(__('common.notification_failed'),__('common.notification_category_delete_failed'));
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
                
                return ['data' => $option, 'message'=>__('common.api_success_form_successfully_subjmit')];
            else:
                return ['data' => $option, 'message'=>__('common.api_error_there_is_an_error_please_try_agin')];
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
                Alert::success(__('common.notification_success'),__('common.notification_product_unit_created_successfully'));
                return redirect(route('addProductUnit'));
            else:
                Alert::error(__('common.notification_failed'),__('common.notification_product_unit_creation_failed'));
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
                Alert::success(__('common.notification_success'),__('common.notification_product_unit_delete_successfully'));
                return back();
        else:
                Alert::error(__('common.notification_failed'),__('common.notification_product_unit_delete_failed'));
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
                
                return ['data' => $option, 'message'=>__('common.api_success_form_successfully_subjmit')];
            else:
                return ['data' => $option, 'message'=>__('common.api_error_there_is_an_error_please_try_agin')];
            endif;
    }

}
