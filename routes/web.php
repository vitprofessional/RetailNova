<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\dashboardController;
use App\Http\Controllers\coustomerSupplier;
use App\Http\Controllers\productController;
use App\Http\Controllers\purchase;
use App\Http\Controllers\saleController;
use App\Http\Controllers\userInfo;
use App\Http\Controllers\expenseController;
use App\Http\Controllers\JqueryController;
use App\Http\Controllers\serviceController;
use App\Http\Controllers\accountController;
use App\Http\Controllers\businessController;
use App\Http\Controllers\invoiceController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\AccountManagementController;
use App\Http\Controllers\ExpenseManagementController;

//user info str
Route::get('/login',[
    userInfo::class,
    'userLogin'
])->name('userLogin');

// Backward-compatible named route expected by Laravel auth middleware
// Some middleware or packages expect a route named `login`. Provide a
// lightweight redirect so `route('login')` resolves and forwards to the
// real login handler `userLogin` without changing existing code.
Route::get('/login-redirect', function(){
    return redirect()->route('userLogin');
})->name('login');

Route::post('creat/admin',[
    userInfo::class,
    'creatAdmin'
])->name('creatAdmin');

Route::post('admin/login',[
    userInfo::class,
    'adminLogin'
])->name('adminLogin');

Route::get('/user/lock/screen',[
    userInfo::class,
    'userLockScreen'
])->name('userLockScreen');

Route::get('/user/recover',[
    userInfo::class,
    'userRecover'
])->name('userRecover');


Route::get('/user/recover/code',[
    userInfo::class,
    'userRecoverCode'
])->name('userRecoverCode');


Route::get('/user/recover/password',[
    userInfo::class,
    'userRecoverPassword'
])->name('userRecoverPassword');


Route::get('/business/setup',[
    userInfo::class,
    'storeCreat'
])->name('storeCreat');

Route::post('/business/save',[
    businessController::class,
    'saveBusiness'
])->name('saveBusiness');

Route::post('/business/logo/save',[
    businessController::class,
    'saveBusinessLogo'
])->name('saveBusinessLogo');

Route::match(['get','post','delete'],'/business/logo/delete/{id}',[
    businessController::class,
    'delBusinessLogo'
])->name('delBusinessLogo');

Route::get('/user/confirm/mail',[
    userInfo::class,
    'userConfirmMail'
])->name('userConfirmMail');

Route::get('/logout',[
    userInfo::class,
    'logout'
])->name('logout');



//user info end
Route::get('/',[
    dashboardController::class,
    'dashboard'
])->name('dashboard');

// Public AJAX endpoint for product list (no auth) — used by sale page to populate product select when
// admin session/cookies are not available to AJAX (keeps UI responsive). This returns option HTML.
Route::get('/ajax/public/customer/{id}/products', [
    JqueryController::class,
    'getProductsForCustomerPublic'
])->name('ajax.customer.products.public');
// Public purchase details by purchase id (returns a single purchase row)
Route::get('/ajax/public/purchase/{id}/details', [
    JqueryController::class,
    'getPurchaseDetailsPublic'
])->name('ajax.purchase.details.public');
// Public product details for dynamic pages (returns JSON)
Route::get('/ajax/public/product/details/{id}', [
    JqueryController::class,
    'getProductDetailsPublic'
])->name('ajax.product.details.public');
// Public AJAX endpoint for sale purchase details (read-only) to avoid guard redirects during dynamic row append
Route::get('/ajax/public/sale/product/{id}/purchase-details', [
    JqueryController::class,
    'getSaleProductDetailsPublic'
])->name('ajax.sale.product.purchaseDetails.public');
// Public endpoint to fetch a customer's aggregate previous due (sum of outstanding due)
Route::get('/ajax/public/customer/{id}/previous-due', [
    JqueryController::class,
    'getCustomerPreviousDuePublic'
])->name('ajax.customer.previousDue.public');
// Require legacy session sync (SuperAdmin) and authenticate via admin guard
Route::middleware([\App\Http\Middleware\SuperAdmin::class, 'auth:admin'])->group(function(){

    // Admin profile management
    Route::get('/admin/profile', [
        AdminProfileController::class,
        'show'
    ])->name('admin.profile.show');

    Route::get('/admin/profile/edit', [
        AdminProfileController::class,
        'edit'
    ])->name('admin.profile.edit');

    Route::post('/admin/profile/update', [
        AdminProfileController::class,
        'update'
    ])->name('admin.profile.update');

    Route::post('/admin/profile/password', [
        AdminProfileController::class,
        'changePassword'
    ])->name('admin.profile.password');

    


    Route::get('/dashboard',[
        dashboardController::class,
        'dashboard'
    ])->name('dashboard');

    //Coustomer&supplier Controller str
    Route::get('/add/customer',[
        coustomerSupplier::class,
        'addCustomer'
    ])->name('addCustomer');

    //coustomer save
    Route::post('/save/customer',[
        coustomerSupplier::class,
        'saveCustomer'
    ])->name('saveCustomer');

    //coustomer edit
    Route::get('/customer/edit/{id}',[
        coustomerSupplier::class,
        'editCustomer'
    ])->name('editCustomer');
    
    //coustomer delete
    Route::match(['post', 'delete'], '/customer/delete/{id}', [
        coustomerSupplier::class,
        'delCustomer'
    ])->name('delCustomer');

    //customer restore
    Route::get('/customer/restore/{id}',[
        coustomerSupplier::class,
        'restoreCustomer'
    ])->name('restoreCustomer');

    // customer bulk delete
    Route::match(['post', 'delete'], '/customer/bulk-delete', [
        coustomerSupplier::class,
        'bulkDeleteCustomer'
    ])->name('customers.bulkDelete');

    
    // submit supplier by ajax
    Route::get('/customer/save',[
        coustomerSupplier::class,
        'createCustomer'
    ])->name('createCustomer');

    //supplier---------------------------

      //supplier add
    Route::get('/add/supplier',[
        coustomerSupplier::class,
        'addSupplier'
    ])->name('addSupplier');

    //supplier save
    Route::post('/save/supplier',[
        coustomerSupplier::class,
        'saveSupplier'
    ])->name('saveSupplier');

    //supplier restore
    Route::get('/supplier/restore/{id}',[
        coustomerSupplier::class,
        'restoreSupplier'
    ])->name('restoreSupplier');

    // submit supplier by ajax
    Route::get('/supplier/save',[
        coustomerSupplier::class,
        'createSupplier'
    ])->name('createSupplier');

    //supplier edit
    Route::get('/supplier/edit/{id}',[
        coustomerSupplier::class,
        'editSupplier'
    ])->name('editSupplier');

    //supplier delete
    Route::match(['post', 'delete'], '/supplier/delete/{id}', [
        coustomerSupplier::class,
        'delSupplier'
    ])->name('delSupplier');
    // supplier bulk delete
    Route::match(['post', 'delete'], '/supplier/bulk-delete', [
        coustomerSupplier::class,
        'bulkDeleteSupplier'
    ])->name('suppliers.bulkDelete');
    //Coustomer&supplier Controller end


    //Product -------------------------

    Route::get('/new/product',[
        productController::class,
        'addProduct'
    ])->name('addProduct');

    //Product save
    Route::post('/save/product',[
        productController::class,
        'saveProduct'
    ])->name('saveProduct');

    //Product edit
    Route::get('/product/edit/{id}',[
        productController::class,
        'editProduct'
    ])->name('editProduct');
    
    //Product delete
    Route::match(['post','delete'], '/product/delete/{id}', [
        productController::class,
        'delProduct'
    ])->name('delProduct');

    //Product list page
    Route::get('/product/list',[
        productController::class,
        'productlist'
    ])->name('productlist');

    // submit product by ajax
    Route::get('/product/save',[
        productController::class,
        'createProduct'
    ])->name('createProduct');

    //stock product----------------------
    
    Route::get('stock/product',[
        productController::class,
        'stockProduct'
    ])->name('stockProduct');


    //brand -----------------------------

    Route::get('/create/brand',[
        productController::class,
        'addBrand'
    ])->name('addBrand');

    //brand save
    Route::post('/save/brand',[
        productController::class,
        'saveBrand'
    ])->name('saveBrand');

    //brand edit
    Route::get('/brand/edit/{id}',[
        productController::class,
        'editBrand'
    ])->name('editBrand');
    
    //brand delete
    Route::match(['get','post','delete'],'/brand/delete/{id}',[
        productController::class,
        'delBrand'
    ])->name('delBrand');

    
    // submit brand by ajax
    Route::get('/brand/save',[
        productController::class,
        'createBrand'
    ])->name('createBrand');

        //category ----------------------
    
    Route::get('/create/category',[
        productController::class,
        'addCategory'
    ])->name('addCategory');

    //category save for
    Route::post('/save/category',[
        productController::class,
        'saveCategory'
    ])->name('saveCategory');

    //category edit
    Route::get('/category/edit/{id}',[
        productController::class,
        'editCategory'
    ])->name('editCategory');
    
    //category delete
    Route::match(['get','post','delete'],'/category/delete/{id}',[
        productController::class,
        'delCategory'
    ])->name('delCategory');

    
    // submit category by ajax
    Route::get('/category/save',[
        productController::class,
        'createCategory'
    ])->name('createCategory');

        //productUnit --------------------------

    Route::get('/create/productUnit',[
        productController::class,
        'addProductUnit'
    ])->name('addProductUnit');

    //productUnit save
    Route::post('/save/productUnit',[
        productController::class,
        'saveProductUnit'
    ])->name('saveProductUnit');

    //productUnit edit
    Route::get('/productUnit/edit/{id}',[
        productController::class,
        'editProductUnit'
    ])->name('editProductUnit');
    
    //productUnit delete
    Route::match(['post','delete'], '/productUnit/delete/{id}', [
        productController::class,
        'delProductUnit'
    ])->name('delProductUnit');

    
    // submit productUnit by ajax
    Route::get('/productUnit/save',[
        productController::class,
        'createProductUnit'
    ])->name('createProductUnit');

    //endProduct

    //damage product str---------------

    //damage product end
        
    Route::get('/damage/product',[
        productController::class,
        'damageProduct'
    ])->name('damageProduct');

    Route::get('/damage/product/list',[
        productController::class,
        'damageProductList'
    ])->name('damageProductList');
        // save damage product
        Route::post('/damage/product/save', [
            productController::class,
            'damageProductSave'
        ])->name('damageProductSave');
        // View a damage record
        Route::get('/damage/product/view/{id}', [
            productController::class,
            'damageProductView'
        ])->name('damageProductView');
        // Printable view
        Route::get('/damage/product/print/{id}', [
            productController::class,
            'damageProductPrint'
        ])->name('damageProductPrint');
        // Delete damage record (DELETE)
        Route::delete('/damage/product/delete/{id}', [
            productController::class,
            'damageProductDelete'
        ])->name('damageProductDelete');

    //purchase str-------------------------

    Route::get('/add/purchase',[
        purchase::class,
        'addPurchase'
    ])->name('addPurchase');

    Route::get('/purchase/list',[
        purchase::class,
        'purchaseList'
    ])->name('purchaseList');

    Route::get('purchase/view/{id}',[
        purchase::class,
        'purchaseView'
    ])->name('purchaseView');

    Route::get('purchase/edit/{id}',[
        purchase::class,
        'editPurchase'
    ])->name('editPurchase');

    Route::post('purchase/update',[
        purchase::class,
        'updatePurchase'
    ])->name('updatePurchase');

    // Support both GET (legacy links) and DELETE (form method spoofing) for deleting purchases
    Route::match(['get','delete'], '/delete/purchase/{id}', [
        purchase::class,
        'delPurchase'
    ])->name('delPurchase');

    Route::get('/return/purchase/{id}',[
        purchase::class,
        'returnPurchase'
    ])->name('returnPurchase');

    Route::post('purchase/return/save',[
        purchase::class,
        'purchaseReturnSave'
    ])->name('purchaseReturnSave');

    Route::get('/return/purchase/list',[
        purchase::class,
        'returnPurchaseList'
    ])->name('returnPurchaseList');
    //Purchase end
    

    //sale start--------------------------

    Route::get('/new/sale',[
        saleController::class,
        'newsale'
    ])->name('newsale')->middleware([\App\Http\Middleware\DebugSession::class]);

    
    Route::get('Sale/list',[
        saleController::class,
        'saleList'
    ])->name('saleList');


    Route::get('generate/invoice/{id}',[
        saleController::class,
        'invoiceGenerate'
    ])->name('invoiceGenerate');

    // Delete sale (revert stock and remove records)
    Route::match(['get','post','delete'],'/sale/delete/{id}',[
        saleController::class,
        'delSale'
    ])->name('delSale');

    Route::get('/return/sale/{id}',[
        saleController::class,
        'returnSale'
    ])->name('returnSale');

    
    Route::get('/return/sale/list',[
        saleController::class,
        'returnSaleList'
    ])->name('returnSaleList');


    Route::post('sale/return/save',[
        saleController::class,
        'saleReturnSave'
    ])->name('saleReturnSave');
    //sale end

    // OLD expense routes removed - replaced by ExpenseManagementController

    // balance_sheet------------------------

        Route::get('/balance/sheet',[
        coustomerSupplier::class,
        'balancesheet'
    ])->name('balancesheet');
    // end_balancesheet


    // supplierbalance
     Route::get('supplier/balance/sheet',[
        coustomerSupplier::class,
        'supplierbalancesheet'
    ])->name('supplierbalancesheet');


    // jquery routes are goes here
    Route::get('product/details/{id}',[
        JqueryController::class,
        'getProductDetails'
    ])->name('getProductDetails');
    
    Route::post('/purchase/save/data',[
        JqueryController::class,
        'savePurchase'
    ])->name('savePurchase');
    
    // add product serial via AJAX
    Route::post('/purchase/serial/add',[
        JqueryController::class,
        'addProductSerial'
    ])->name('addProductSerial');
    
    // delete product serial (AJAX)
    Route::match(['get','post','delete'],'product/serial/delete/{id}', [
        JqueryController::class,
        'deleteProductSerial'
    ])->name('deleteProductSerial');
    
    Route::get('sale/product/details/{id}',[
        JqueryController::class,
        'getSaleProductDetails'
    ])->name('getSaleProductDetails');

    // AJAX: get products for a given customer (returns option HTML)
    Route::get('ajax/customer/{id}/products',[
        JqueryController::class,
        'getProductsForCustomer'
    ])->name('ajax.customer.products');
    
    Route::get('purchase/details/{id}',[
        JqueryController::class,
        'getPurchaseDetails'
    ])->name('getPurchaseDetails');

    
    Route::get('service/details/{id}',[
        JqueryController::class,
        'getServiceDetails'
    ])->name('getServiceDetails');
    Route::get('/calculate-grand-total', [
        JqueryController::class, 
        'calculateGrandTotal'
    ])->name('calculate.grand.total');

    // endsupplierbalance

    
    //Service------------------------

    //add service name
    Route::get('create/service/',[
        serviceController::class,
        'addServiceName'
    ])->name('addServiceName');

    //save service
    Route::post('/save/service',[
        serviceController::class,
        'saveService'
    ])->name('saveService');
    
    //service edit
    Route::get('/service/edit/{id}',[
        serviceController::class,
        'editService'
    ])->name('editService');

    
    //service delete (use DELETE to match forms)
    Route::delete('/service/delete/{id}',[
        serviceController::class,
        'delService'
    ])->name('delService');

    // save provided service entries
    Route::post('/save/provideService',[
        serviceController::class,
        'saveProvideService'
    ])->name('saveProvideService');

    // service bulk delete (accept both POST and DELETE for compatibility)
    Route::match(['post','delete'], '/service/bulk-delete', [
        serviceController::class,
        'bulkDeleteService'
    ])->name('services.bulkDelete');

    Route::get('provide/service/',[
        serviceController::class,
        'provideService'
    ])->name('provideService');

    Route::get('provide/service/list',[
        serviceController::class,
        'serviceProvideList'
    ])->name('serviceProvideList');
    Route::delete('provide/service/delete/{id}',[
        serviceController::class,
        'delProvideService'
    ])->name('delProvideService');
    Route::get('provide/service/view/{id}',[
        serviceController::class,
        'provideServiceView'
    ])->name('provideServiceView');

    // Service Invoice routes (view and print)
    Route::get('service/invoice/{id}',[
        serviceController::class,
        'serviceInvoiceView'
    ])->name('serviceInvoiceView');

    Route::get('service/invoice/{id}/print',[
        serviceController::class,
        'serviceInvoicePrint'
    ])->name('serviceInvoicePrint');

    // Get next invoice number (AJAX preview)
    Route::get('service/next-invoice-number',[
        serviceController::class,
        'getNextInvoiceNumber'
    ])->name('service.nextInvoiceNumber');
    Route::get('provide/service/print/{id}',[
        serviceController::class,
        'provideServicePrint'
    ])->name('provideServicePrint');

    // Warranty pages (RMA and Serials)
    // Protect RMA routes for admin users
    Route::middleware([\App\Http\Middleware\SuperAdmin::class,'auth:admin'])->group(function(){
        Route::get('/warranty/rma', [\App\Http\Controllers\RmaController::class, 'index'])->name('rma.index');
        Route::get('/warranty/rma/create', [\App\Http\Controllers\RmaController::class, 'create'])->name('rma.create');
        Route::post('/warranty/rma', [\App\Http\Controllers\RmaController::class, 'store'])->name('rma.store');
        Route::get('/warranty/rma/{id}', [\App\Http\Controllers\RmaController::class, 'show'])->name('rma.show');
        Route::get('/warranty/rma/{id}/edit', [\App\Http\Controllers\RmaController::class, 'edit'])->name('rma.edit');
        Route::put('/warranty/rma/{id}', [\App\Http\Controllers\RmaController::class, 'update'])->name('rma.update');
        Route::delete('/warranty/rma/{id}', [\App\Http\Controllers\RmaController::class, 'destroy'])->name('rma.destroy');
        // RMA export
        Route::get('/warranty/rma/export', [\App\Http\Controllers\RmaController::class, 'export'])->name('rma.export');
    });

    // Serial list is public to authenticated users; protect export and ajax lookup where appropriate
    Route::get('/warranty/serials', [\App\Http\Controllers\WarrantyController::class, 'serialList'])->name('serialList');
    Route::middleware([\App\Http\Middleware\SuperAdmin::class,'auth:admin'])->group(function(){
        Route::get('/warranty/serials/export', [\App\Http\Controllers\WarrantyController::class, 'exportSerials'])->name('serials.export');
    });

    // AJAX endpoint for serial lookup (authenticated)
    Route::get('/ajax/serials', [\App\Http\Controllers\WarrantyController::class, 'ajaxSerials'])->name('ajax.serials')->middleware('auth');

    // Admin report: list provide_services rows missing rate or qty
    Route::get('admin/provide-services/missing-data', [
        serviceController::class,
        'provideServicesMissingData'
    ])->name('admin.provideServices.missing')->middleware([\App\Http\Middleware\SuperAdmin::class,'auth:admin']);

    Route::get('admin/provide-services/missing-data/export', [
        serviceController::class,
        'exportProvideServicesMissing'
    ])->name('admin.provideServices.missing.export')->middleware([\App\Http\Middleware\SuperAdmin::class,'auth:admin']);

    // OLD account routes removed - replaced by AccountManagementController

    // Account Management System Routes
    Route::prefix('accounts')->name('account.')->group(function () {
        // Chart of Accounts
        Route::get('/chart', [AccountManagementController::class, 'chartOfAccounts'])->name('chart');
        Route::get('/create', [AccountManagementController::class, 'createAccount'])->name('create');
        Route::post('/store', [AccountManagementController::class, 'storeAccount'])->name('store');
        Route::get('/edit/{id}', [AccountManagementController::class, 'editAccount'])->name('edit');
        Route::post('/update/{id}', [AccountManagementController::class, 'updateAccount'])->name('update');
        Route::match(['get','post','delete'],'/delete/{id}', [AccountManagementController::class, 'deleteAccount'])->name('delete');
        
        // Transactions
        Route::get('/transactions', [AccountManagementController::class, 'transactionList'])->name('transactions');
        Route::get('/transactions/create', [AccountManagementController::class, 'createTransaction'])->name('transactions.create');
        Route::post('/transactions/store', [AccountManagementController::class, 'storeTransaction'])->name('transactions.store');
        
        // Ledger
        Route::get('/ledger/{id}', [AccountManagementController::class, 'accountLedger'])->name('ledger');
        
        // Financial Reports
        Route::get('/reports', [AccountManagementController::class, 'financialReports'])->name('reports');
    });

    // Expense Management System Routes
    Route::prefix('expenses')->name('expense.')->group(function () {
        // Categories
        Route::get('/categories', [ExpenseManagementController::class, 'expenseCategories'])->name('categories');
        Route::get('/categories/create', [ExpenseManagementController::class, 'createCategory'])->name('categories.create');
        Route::post('/categories/store', [ExpenseManagementController::class, 'storeCategory'])->name('categories.store');
        Route::get('/categories/edit/{id}', [ExpenseManagementController::class, 'editCategory'])->name('categories.edit');
        Route::post('/categories/update/{id}', [ExpenseManagementController::class, 'updateCategory'])->name('categories.update');
        Route::match(['get','post','delete'],'/categories/delete/{id}', [ExpenseManagementController::class, 'deleteCategory'])->name('categories.delete');
        
        // Expense Entries
        Route::get('/list', [ExpenseManagementController::class, 'expenseList'])->name('list');
        Route::get('/create', [ExpenseManagementController::class, 'createExpense'])->name('create');
        Route::post('/store', [ExpenseManagementController::class, 'storeExpense'])->name('store');
        Route::get('/edit/{id}', [ExpenseManagementController::class, 'editExpense'])->name('edit');
        Route::post('/update/{id}', [ExpenseManagementController::class, 'updateExpense'])->name('update');
        Route::match(['get','post','delete'],'/delete/{id}', [ExpenseManagementController::class, 'deleteExpense'])->name('delete');
        
        // Reports
        Route::get('/reports', [ExpenseManagementController::class, 'expenseReports'])->name('reports');
        Route::get('/statistics', [ExpenseManagementController::class, 'expenseStatistics'])->name('statistics');
    });

    Route::post('/sale/save/data',[
        JqueryController::class,
        'saveSale'
    ])->name('saveSale');

    //Business set up --------------------

    //set up page
    Route::get('Business/setup/',[
        businessController::class,
        'addBusinessSetupPage'
    ])->name('addBusinessSetupPage');

    // Business Locations
    Route::get('business/locations', [
        businessController::class,
        'locationsList'
    ])->name('business.locations');

    Route::get('business/locations/create', [
        businessController::class,
        'createLocation'
    ])->name('business.locations.create');

    Route::post('business/locations/store', [
        businessController::class,
        'storeLocation'
    ])->name('business.locations.store');

    Route::get('business/locations/{id}/edit', [
        businessController::class,
        'editLocation'
    ])->name('business.locations.edit');

    Route::post('business/locations/{id}/update', [
        businessController::class,
        'updateLocation'
    ])->name('business.locations.update');

    Route::match(['get','post','delete'],'business/locations/{id}/delete', [
        businessController::class,
        'deleteLocation'
    ])->name('business.locations.delete');

    //invoice------------------------------
    //invoice view
    Route::get('/invoice',[
        invoiceController::class,
        'invoicePage'
    ])->name('invoicePage');

    // audits viewer
    Route::get('/audits',[\App\Http\Controllers\AuditController::class,'index'])->name('audits.index');
    Route::get('/audits/export',[\App\Http\Controllers\AuditController::class,'export'])->name('audits.export');

    // Bulk delete routes (protected)
    Route::post('/products/bulk-delete',[productController::class,'bulkDeleteProducts'])->name('products.bulkDelete');
    Route::post('/brands/bulk-delete',[productController::class,'bulkDeleteBrands'])->name('brands.bulkDelete');
    Route::post('/categories/bulk-delete',[productController::class,'bulkDeleteCategories'])->name('categories.bulkDelete');
    Route::post('/units/bulk-delete',[productController::class,'bulkDeleteUnits'])->name('units.bulkDelete');
    Route::post('/damage-products/bulk-delete',[productController::class,'bulkDeleteDamageProducts'])->name('damageProducts.bulkDelete');
    Route::post('/purchases/bulk-delete',[purchase::class,'bulkDeletePurchases'])->name('purchases.bulkDelete');
    Route::post('/sales/bulk-delete',[saleController::class,'bulkDeleteSales'])->name('sales.bulkDelete');
    Route::post('/provided-services/bulk-delete',[serviceController::class,'bulkDeleteProvidedServices'])->name('providedServices.bulkDelete');
    Route::post('/provided-services/bulk-print',[serviceController::class,'bulkPrintProvidedServices'])->name('providedServices.bulkPrint');
    Route::post('/provided-services/bulk-print/pdf',[serviceController::class,'bulkPrintProvidedServicesPdf'])->name('providedServices.bulkPrintPdf');

    // Report Routes
    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/business', [\App\Http\Controllers\ReportController::class, 'businessReport'])->name('reports.business');
    Route::get('/reports/sales', [\App\Http\Controllers\ReportController::class, 'saleReport'])->name('reports.sales');
    Route::get('/reports/purchases', [\App\Http\Controllers\ReportController::class, 'purchaseReport'])->name('reports.purchases');
    Route::get('/reports/top-customers', [\App\Http\Controllers\ReportController::class, 'topCustomers'])->name('reports.topCustomers');
    Route::get('/reports/payable-receivable', [\App\Http\Controllers\ReportController::class, 'payableReceivable'])->name('reports.payableReceivable');
    Route::get('/reports/stock', [\App\Http\Controllers\ReportController::class, 'stockReport'])->name('reports.stock');

    // Documentation Routes
    Route::get('/documentation', [\App\Http\Controllers\DocumentationController::class, 'index'])->name('documentation.index');
    Route::get('/documentation/print', [\App\Http\Controllers\DocumentationController::class, 'print'])->name('documentation.print');
    Route::get('/documentation/download-pdf', [\App\Http\Controllers\DocumentationController::class, 'downloadPdf'])->name('documentation.downloadPdf');
    Route::get('/documentation/{section}', [\App\Http\Controllers\DocumentationController::class, 'show'])->name('documentation.show');
    Route::get('/documentation/{section}/download-pdf', [\App\Http\Controllers\DocumentationController::class, 'downloadSectionPdf'])->name('documentation.sectionPdf');
});