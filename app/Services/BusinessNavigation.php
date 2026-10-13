<?php

namespace App\Services;

use App\Models\BusinessSetup;

class BusinessNavigation
{
    public function groups(): array
    {
        $actor=auth('admin')->user();
        $role=strtolower((string)$actor?->role);
        $finance=!in_array($role,['storemanager','salesmanager'],true);
        $business=$actor?->businessId ? BusinessSetup::find($actor->businessId) : null;
        // An unassigned platform owner may see the sole business, never a globally latest record.
        if(!$business && $actor?->hasSuperAdminPrivileges() && BusinessSetup::count()===1){$business=BusinessSetup::first();}
        $type=$business?->businessType;
        $service=in_array($type,['mobile_shop','vehicle_shop','computer_shop','electronics_parts_shop','hardware_shop','dealership_shop'],true);
        $quotations=in_array($type,['vehicle_shop','computer_shop','electronics_parts_shop','hardware_shop','dealership_shop','stationary_shop','garments_shop'],true);
        $item=fn($route,$key,$icon,$active=[])=>['route'=>$route,'key'=>$key,'icon'=>$icon,'active'=>$active?:[$route]];
        $group=fn($id,$key,$icon,$items)=>['id'=>$id,'key'=>$key,'icon'=>$icon,'items'=>$items];
        $groups=[];
        $groups[]=$group('dashboard','dashboard','la-tachometer-alt',[$item('dashboard','dashboard','la-tachometer-alt')]);
        $sales=[$item('newsale','new_sale','la-cart-plus',['newsale','editSaleItems','sale.invoice.*']),$item('saleList','sales_history','la-history',['saleList','viewSale','editSale'])];
        if($quotations){$sales[]=$item('quotation.create','new_quotation','la-file-alt',['quotation.create']);$sales[]=$item('quotation.list','quotation_history','la-list',['quotation.list','quotation.show','quotation.edit']);}
        $groups[]=$group('sale','sales_management','la-shopping-cart',$sales);
        $groups[]=$group('parchase','purchase_management','la-shopping-bag',[$item('addPurchase','new_purchase','la-plus-circle'),$item('purchaseList','purchase_history','la-history',['purchaseList','purchase.invoice.*','purchaseView','editPurchase'])]);
        $groups[]=$group('category','inventory_management','la-boxes',[
            $item('productlist','product_catalog','la-list',['productlist','editProduct']),$item('addProduct','new_product','la-plus-circle'),$item('stockProduct','stock_overview','la-warehouse'),
            $item('addBrand','brands','la-tag'),$item('addCategory','categories','la-tags'),$item('addProductUnit','units','la-ruler'),$item('barcode.index','barcodes','la-barcode',['barcode.*']),
            $item('damageProduct','record_damage','la-exclamation-triangle'),$item('damageProductList','damage_history','la-history')]);
        $groups[]=$group('product','customers_suppliers','la-address-book',[$item('addCustomer','customer_directory','la-user-friends',['addCustomer','editCustomer','customerProfile']),$item('addSupplier','supplier_directory','la-truck',['addSupplier','editSupplier','supplierProfile'])]);
        if($service){$groups[]=$group('service','service_management','la-tools',[$item('provideService','new_service','la-plus-circle'),$item('serviceProvideList','service_history','la-history'),$item('addServiceName','service_catalog','la-list')]);}
        $returns=[$item('returnSaleList','sales_returns','la-undo',['returnSaleList','returnSale']),$item('returnPurchaseList','purchase_returns','la-reply',['returnPurchaseList','returnPurchase'])];
        if($service){$returns[]=$item('rma.index','warranty_claims','la-shield-alt',['rma.*']);$returns[]=$item('serialList','serial_registry','la-barcode');}
        $groups[]=$group('warranty','warranty_returns','la-undo',$returns);
        if($finance){
            $groups[]=$group('account','financial_management','la-wallet',[$item('account.chart','chart_of_accounts','la-book',['account.chart','account.create','account.edit']),$item('account.transactions','transactions','la-exchange-alt',['account.transactions','account.transactions.*']),$item('account.reports','financial_reports','la-chart-line'),
                $item('expense.create','new_expense','la-plus-circle'),$item('expense.list','expense_history','la-list',['expense.list','expense.edit']),$item('expense.categories','expense_categories','la-tags',['expense.categories','expense.categories.*']),$item('expense.reports','expense_reports','la-chart-bar'),$item('audits.index','audit_history','la-clipboard-list',['audits.*'])]);
            $groups[]=$group('reports','reports_analytics','la-chart-bar',[$item('reports.business','business_report','la-chart-pie'),$item('reports.sales','sale_report','la-chart-line'),$item('reports.purchases','purchase_report','la-chart-area'),$item('reports.stock','stock_report','la-box'),$item('reports.topCustomers','top_customers','la-users'),$item('reports.payableReceivable','payable_receivable','la-balance-scale')]);
            $settings=[$item('addBusinessSetupPage','general_settings','la-cog'),$item('business.locations','business_locations','la-map-marker-alt',['business.locations','business.locations.*'])];
            if($actor?->canManageSalesSettings()){$settings[]=$item('sales.settings.edit','sales_settings','la-sliders-h',['sales.settings.*']);}
            if(in_array(strtolower(trim((string)$actor?->role)),['admin','superadmin'],true)){$settings[]=$item('language.settings.edit','language_preferences','la-language',['language.settings.*']);}
            $groups[]=$group('settings','business_settings','la-cog',$settings);
        }
        if(!$finance && $actor?->canManageSalesSettings()){$groups[]=$group('settings','business_settings','la-cog',[$item('sales.settings.edit','sales_settings','la-sliders-h',['sales.settings.*'])]);}
        if(in_array($role,['admin','gm'],true)){$groups[]=$group('users','user_management','la-users-cog',[$item('admin.manage.users.index','user_management','la-users-cog',['admin.manage.users.*'])]);}
        if($role==='super admin'){$groups[]=$group('superadmin','platform_management','la-user-shield',[$item('admin.super.users.index','user_management','la-users',['admin.super.users.*']),$item('admin.super.subscriptions.plans','subscription_plans','la-list'),$item('admin.super.subscriptions.index','subscriptions','la-credit-card'),$item('admin.super.stores.index','stores','la-store'),$item('admin.super.gateways.index','payment_gateways','la-credit-card')]);}
        $groups[]=$group('help','documentation','la-book-open',[$item('documentation.index','documentation','la-book-open',['documentation.*'])]);
        return $groups;
    }
}
