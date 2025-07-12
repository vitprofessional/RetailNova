<?php

namespace App\Http\Controllers;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductUnit;
use App\Models\PurchaseProduct;

use Illuminate\Http\Request;

class purchase extends Controller
{
   public function addPurchase(){
        $supplier = Supplier::orderBy('id','DESC')->get();
        $product = Product::orderBy('id','DESC')->get();
        $productUnit = ProductUnit::orderBy('id','DESC')->get();
        $category = Category::orderBy('id','DESC')->get();
        $brand = Brand::orderBy('id','DESC')->get();
    return view('purchase.addPurchase',['brandList'=>$brand,'categoryList'=>$category,'productUnitList'=>$productUnit,'supplierList'=>$supplier,'productList'=>$product]);
   }

   public function purchaseList(){
      $purchaseList = PurchaseProduct::join('products','products.id','purchase_products.productName')
      ->join('suppliers','suppliers.id','purchase_products.supplier')
      ->join('product_stocks','product_stocks.purchaseId','purchase_products.id')
      ->select(
            'purchase_products.id as purchaseId',
            'products.id as productId',
            'products.name as productName',
            'suppliers.id as supplierId',
            'suppliers.name as supplierName',
            'product_stocks.id as stockId',
            'product_stocks.currentStock',
            'purchase_products.invoice',
            'purchase_products.qty',
            'purchase_products.totalAmount',
            'purchase_products.grandTotal',
            'purchase_products.paidAmount',
            'purchase_products.paidAmount',
            'purchase_products.dueAmount',
            'purchase_products.salePriceExVat',
            'purchase_products.salePriceInVat',
            'purchase_products.vatStatus',
            'purchase_products.buyPrice',
            'purchase_products.purchase_date',
            'purchase_products.id',
      )->orderBy('totalAmount','desc')->get();
      return view('purchase.purchaseList',['purchaseList'=>$purchaseList]);
   }
}
