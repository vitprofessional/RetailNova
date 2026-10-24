# পাতার তালিকা (UI-2, ধাপ ১)

রুট-ভিত্তিক তালিকা, ২০২৬-১০-২৪-এর কোড থেকে (`php artisan route:list --method=GET`, প্রতিটা কন্ট্রোলার মেথড আর তার view দেখে)।

**মূল কথা:** কোনো পাতাই DataTables AJAX (server-side) ব্যবহার করে না। সব তালিকা সার্ভারে পুরো HTML টেবিল বানায়; কিছু পাতায় DataTables শুধু ব্রাউজারে সাজানো/খোঁজা/পেজিং করে। কয়েকটা পাতায় Laravel-এর নিজস্ব পেজিনেশন। তাই মোবাইলের "আরও দেখুন"-এর জন্য নতুন শুধু-পড়ার JSON endpoint বানানো হয়েছে: `GET /m/lists/{list}` (`m.lists`)।

মোট 153 GET রুট: ফর্ম 40, তালিকা 32, অন্য 71, রিপোর্ট 10। "মোবাইল" কলামে যেসব পাতার আলাদা মোবাইল ভার্সন আছে; বাকিগুলো মোবাইলে UI-1-এর শেলের ভেতরে ডেস্কটপের মার্কআপ দেখায়।

| রুট | নাম | ধরন | ডেটা কীভাবে আসে | মোবাইল |
|---|---|---|---|---|
| `/` | — | ফর্ম — লগইন (/) | সার্ভারে রেন্ডার |  |
| `/Business/setup` | `addBusinessSetupPage` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/Sale/list` | `saleList` | তালিকা | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) | UI-2 |
| `/accounts/chart` | `account.chart` | তালিকা | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) | UI-3.L |
| `/accounts/create` | `account.create` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/accounts/delete/{id}` | `account.delete` | অন্য | অ্যাকশন/রিডাইরেক্ট (পাতা নয়) |  |
| `/accounts/edit/{id}` | `account.edit` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/accounts/ledger/{id}` | `account.ledger` | তালিকা | সার্ভারে রেন্ডার | UI-3.11 |
| `/accounts/reports` | `account.reports` | রিপোর্ট | সার্ভারে রেন্ডার | UI-3Z.4 |
| `/accounts/transactions` | `account.transactions` | তালিকা | সার্ভারে রেন্ডার, পেজিনেশন ৫০ | UI-3.L |
| `/accounts/transactions/create` | `account.transactions.create` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/add/customer` | `addCustomer` | তালিকা — তালিকা + যোগ করার ফর্ম একই পাতায় | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) | UI-2 |
| `/add/purchase` | `addPurchase` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/add/supplier` | `addSupplier` | তালিকা — তালিকা + যোগ করার ফর্ম একই পাতায় | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) | UI-2 |
| `/admin/manage/users` | `admin.manage.users.index` | তালিকা | সার্ভারে রেন্ডার, পেজিনেশন (ফর্ম মডালে) | UI-3.S |
| `/admin/manage/users/{id}/edit` | `admin.manage.users.edit` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/admin/profile` | `admin.profile.show` | অন্য | সার্ভারে রেন্ডার |  |
| `/admin/profile/edit` | `admin.profile.edit` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/admin/provide-services/missing-data` | `admin.provideServices.missing` | তালিকা | সার্ভারে রেন্ডার | UI-3.S |
| `/admin/provide-services/missing-data/export` | `admin.provideServices.missing.export` | অন্য | ফাইল ডাউনলোড |  |
| `/admin/super/gateways` | `admin.super.gateways.index` | তালিকা — তালিকা + ফর্ম | সার্ভারে রেন্ডার | UI-3.S |
| `/admin/super/stores` | `admin.super.stores.index` | তালিকা | সার্ভারে রেন্ডার | UI-3.S |
| `/admin/super/subscriptions` | `admin.super.subscriptions.index` | তালিকা | সার্ভারে রেন্ডার, পেজিনেশন | UI-3.S |
| `/admin/super/subscriptions/plans` | `admin.super.subscriptions.plans` | তালিকা — তালিকা + ফর্ম | সার্ভারে রেন্ডার | UI-3.S |
| `/admin/super/subscriptions/{id}/cancel` | `admin.super.subscriptions.cancel` | অন্য | অ্যাকশন/রিডাইরেক্ট (পাতা নয়) |  |
| `/admin/super/users` | `admin.super.users.index` | তালিকা | সার্ভারে রেন্ডার, পেজিনেশন (ফর্ম মডালে) | UI-3.S |
| `/admin/super/users/{id}/edit` | `admin.super.users.edit` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/ajax/customer/{id}/products` | `ajax.customer.products` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/ajax/public/brands` | `ajax.brands.public` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/ajax/public/categories` | `ajax.categories.public` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/ajax/public/category/{id}/products` | `ajax.category.products.public` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/ajax/public/customer/{id}/previous-due` | `ajax.customer.previousDue.public` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/ajax/public/customer/{id}/products` | `ajax.customer.products.public` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/ajax/public/product/details/{id}` | `ajax.product.details.public` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/ajax/public/purchase/{id}/details` | `ajax.purchase.details.public` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/ajax/public/purchase/{id}/serials` | `ajax.purchase.serials.public` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/ajax/public/sale/product/{id}/purchase-details` | `ajax.sale.product.purchaseDetails.public` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/ajax/serials` | `ajax.serials` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/audits` | `audits.index` | তালিকা | সার্ভারে রেন্ডার, পেজিনেশন | UI-3.L.2 |
| `/audits/export` | `audits.export` | অন্য | ফাইল ডাউনলোড |  |
| `/balance/sheet` | `balancesheet` | রিপোর্ট | সার্ভারে রেন্ডার | UI-3.R.2 (legacy statement contract unresolved) |
| `/barcode` | `barcode.index` | তালিকা | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) | UI-3.L.2 |
| `/barcode/generate-all-missing` | `generateAllMissingBarcodes` | অন্য | অ্যাকশন/রিডাইরেক্ট (পাতা নয়) |  |
| `/barcode/generate/{id}` | `barcode.generate` | অন্য | সার্ভারে রেন্ডার |  |
| `/brand/delete/{id}` | `delBrand` | অন্য | অ্যাকশন/রিডাইরেক্ট (পাতা নয়) |  |
| `/brand/edit/{id}` | `editBrand` | ফর্ম — এডিট ফর্ম + তালিকা একই পাতায় | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) |  |
| `/brand/save` | `createBrand` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/brochure` | `brochure.marketing` | অন্য | অ্যাকশন/রিডাইরেক্ট (পাতা নয়) |  |
| `/business/language-settings` | `language.settings.edit` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/business/locations` | `business.locations` | তালিকা | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) | UI-3.L.2 |
| `/business/locations/create` | `business.locations.create` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/business/locations/{id}/delete` | `business.locations.delete` | অন্য | অ্যাকশন/রিডাইরেক্ট (পাতা নয়) |  |
| `/business/locations/{id}/edit` | `business.locations.edit` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/business/sales-settings` | `sales.settings.edit` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/business/setup` | `storeCreat` | অন্য | অ্যাকশন/রিডাইরেক্ট (পাতা নয়) |  |
| `/calculate-grand-total` | `calculate.grand.total` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/category/delete/{id}` | `delCategory` | অন্য | অ্যাকশন/রিডাইরেক্ট (পাতা নয়) |  |
| `/category/edit/{id}` | `editCategory` | ফর্ম — এডিট ফর্ম + তালিকা একই পাতায় | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) |  |
| `/category/save` | `createCategory` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/create/brand` | `addBrand` | তালিকা — তালিকা + যোগ করার ফর্ম একই পাতায় | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) | UI-3.4b |
| `/create/category` | `addCategory` | তালিকা — তালিকা + যোগ করার ফর্ম একই পাতায় | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) | UI-3.4b |
| `/create/productUnit` | `addProductUnit` | তালিকা — তালিকা + যোগ করার ফর্ম একই পাতায় | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) | UI-3.4b |
| `/create/service` | `addServiceName` | তালিকা — তালিকা + যোগ করার ফর্ম একই পাতায় | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) | UI-3.4b |
| `/customer/edit/{id}` | `editCustomer` | ফর্ম — এডিট ফর্ম + তালিকা একই পাতায় | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) | UI-2 (ফর্ম) |
| `/customer/restore/{id}` | `restoreCustomer` | অন্য | অ্যাকশন/রিডাইরেক্ট (পাতা নয়) |  |
| `/damage/product` | `damageProduct` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/damage/product/list` | `damageProductList` | তালিকা | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) | UI-3.L |
| `/damage/product/print/{id}` | `damageProductPrint` | অন্য | সার্ভারে রেন্ডার |  |
| `/damage/product/view/{id}` | `damageProductView` | অন্য | সার্ভারে রেন্ডার |  |
| `/dashboard` | `dashboard` | অন্য — ড্যাশবোর্ড | সার্ভারে রেন্ডার + JSON (dashboard.metrics) + Chart.js | UI-1 (হোম) |
| `/dashboard/metrics` | `dashboard.metrics` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/delete/purchase/{id}` | `delPurchase` | অন্য | অ্যাকশন/রিডাইরেক্ট (পাতা নয়) |  |
| `/documentation` | `documentation.index` | অন্য | সার্ভারে রেন্ডার |  |
| `/documentation/download-pdf` | `documentation.downloadPdf` | অন্য | সার্ভারে রেন্ডার |  |
| `/documentation/print` | `documentation.print` | অন্য | সার্ভারে রেন্ডার |  |
| `/documentation/{section}` | `documentation.show` | অন্য | সার্ভারে রেন্ডার |  |
| `/documentation/{section}/download-pdf` | `documentation.sectionPdf` | অন্য | সার্ভারে রেন্ডার |  |
| `/expenses/categories` | `expense.categories` | তালিকা | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) | UI-3.13 |
| `/expenses/categories/create` | `expense.categories.create` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/expenses/categories/delete/{id}` | `expense.categories.delete` | অন্য | অ্যাকশন/রিডাইরেক্ট (পাতা নয়) |  |
| `/expenses/categories/edit/{id}` | `expense.categories.edit` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/expenses/create` | `expense.create` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/expenses/delete/{id}` | `expense.delete` | অন্য | অ্যাকশন/রিডাইরেক্ট (পাতা নয়) |  |
| `/expenses/edit/{id}` | `expense.edit` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/expenses/list` | `expense.list` | তালিকা | সার্ভারে রেন্ডার, পেজিনেশন ৫০ + DataTables (ব্রাউজারে) | UI-2 |
| `/expenses/reports` | `expense.reports` | রিপোর্ট | সার্ভারে রেন্ডার | UI-3Z.4 |
| `/expenses/statistics` | `expense.statistics` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/generate/invoice/{id}` | `invoiceGenerate` | অন্য | সার্ভারে রেন্ডার |  |
| `/invoice` | `invoicePage` | অন্য | সার্ভারে রেন্ডার |  |
| `/invoice/thermal-print` | `invoiceThermalPrint` | অন্য | সার্ভারে রেন্ডার |  |
| `/login` | `userLogin` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/login-redirect` | `login` | অন্য | অ্যাকশন/রিডাইরেক্ট (পাতা নয়) |  |
| `/logout` | `logout` | অন্য | অ্যাকশন/রিডাইরেক্ট (পাতা নয়) |  |
| `/new/product` | `addProduct` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/new/sale` | `newsale` | ফর্ম — মোবাইলে UI-1-এর POS এই ফর্মটাই চালায় | সার্ভারে রেন্ডার | UI-1 (POS) |
| `/product/details/{id}` | `getProductDetails` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/product/edit/{id}` | `editProduct` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/product/list` | `productlist` | তালিকা | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) | UI-2 |
| `/product/serial/delete/{id}` | `deleteProductSerial` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/productUnit/edit/{id}` | `editProductUnit` | ফর্ম — এডিট ফর্ম + তালিকা একই পাতায় | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) |  |
| `/productUnit/save` | `createProductUnit` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/provide/service` | `provideService` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/provide/service/list` | `serviceProvideList` | তালিকা | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) | UI-3.L |
| `/provide/service/print/{id}` | `provideServicePrint` | অন্য | সার্ভারে রেন্ডার |  |
| `/provide/service/view/{id}` | `provideServiceView` | অন্য | সার্ভারে রেন্ডার |  |
| `/purchase/details/{id}` | `getPurchaseDetails` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/purchase/edit/{id}` | `editPurchase` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/purchase/invoices/{id}` | `purchase.invoice.show` | অন্য | সার্ভারে রেন্ডার |  |
| `/purchase/invoices/{id}/edit` | `purchase.invoice.edit` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/purchase/list` | `purchaseList` | তালিকা | সার্ভারে রেন্ডার, পেজিনেশন ২০ (Laravel), ফিল্টার কোয়েরি-স্ট্রিংয়ে | UI-2 |
| `/purchase/view/{id}` | `purchaseView` | অন্য | সার্ভারে রেন্ডার |  |
| `/quotation/create` | `quotation.create` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/quotation/list` | `quotation.list` | তালিকা | সার্ভারে রেন্ডার, পেজিনেশন ২০ | UI-3.L |
| `/quotation/{id}` | `quotation.show` | অন্য | সার্ভারে রেন্ডার |  |
| `/quotation/{id}/edit` | `quotation.edit` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/quotation/{id}/print` | `quotation.print` | অন্য | সার্ভারে রেন্ডার |  |
| `/reports` | `reports.index` | রিপোর্ট | সার্ভারে রেন্ডার | UI-3Z.4 |
| `/reports/business` | `reports.business` | রিপোর্ট | সার্ভারে রেন্ডার | UI-3Z.4 (SQLite verified) |
| `/reports/payable-receivable` | `reports.payableReceivable` | রিপোর্ট | সার্ভারে রেন্ডার | UI-3Z.4 |
| `/reports/purchases` | `reports.purchases` | রিপোর্ট | সার্ভারে রেন্ডার | UI-3Z.4 |
| `/reports/sales` | `reports.sales` | রিপোর্ট | সার্ভারে রেন্ডার, পেজিনেশন ৫০ | UI-3Z.4 |
| `/reports/stock` | `reports.stock` | রিপোর্ট | সার্ভারে রেন্ডার, পেজিনেশন ৫০ | UI-3Z.4 |
| `/reports/top-customers` | `reports.topCustomers` | রিপোর্ট | সার্ভারে রেন্ডার | UI-3Z.4 |
| `/return/purchase/list` | `returnPurchaseList` | তালিকা | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) | UI-3.L |
| `/return/purchase/{id}` | `returnPurchase` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/return/sale/details/{id}` | `saleReturnDetails` | অন্য | সার্ভারে রেন্ডার |  |
| `/return/sale/list` | `returnSaleList` | তালিকা | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) | UI-3.L |
| `/return/sale/{id}` | `returnSale` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/sale/edit/{id}` | `sale.edit` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/sale/items/edit/{id}` | `sale.items.edit` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/sale/product/details/{id}` | `getSaleProductDetails` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/service/details/{id}` | `getServiceDetails` | অন্য | ভাঙা রুট: কন্ট্রোলারে মেথড নেই |  |
| `/service/edit/{id}` | `editService` | ফর্ম — এডিট ফর্ম + তালিকা একই পাতায় | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) |  |
| `/service/invoice/{id}` | `serviceInvoiceView` | অন্য | সার্ভারে রেন্ডার |  |
| `/service/invoice/{id}/print` | `serviceInvoicePrint` | অন্য | সার্ভারে রেন্ডার |  |
| `/service/next-invoice-number` | `service.nextInvoiceNumber` | অন্য | AJAX JSON (পাতা নয়) |  |
| `/setup/superadmin` | `superadmin.setup.show` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/stock/product` | `stockProduct` | তালিকা | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) | UI-3.L (lot cards) |
| `/supplier/balance/sheet` | `supplierbalancesheet` | অন্য | ভাঙা রুট: কন্ট্রোলারে মেথড নেই |  |
| `/supplier/edit/{id}` | `editSupplier` | ফর্ম — এডিট ফর্ম + তালিকা একই পাতায় | সার্ভারে রেন্ডার + DataTables (ব্রাউজারে সাজানো/খোঁজা) | UI-2 (ফর্ম) |
| `/supplier/restore/{id}` | `restoreSupplier` | অন্য | অ্যাকশন/রিডাইরেক্ট (পাতা নয়) |  |
| `/user/confirm/mail` | `userConfirmMail` | অন্য | সার্ভারে রেন্ডার |  |
| `/user/lock/screen` | `userLockScreen` | অন্য | সার্ভারে রেন্ডার |  |
| `/user/recover` | `userRecover` | অন্য | সার্ভারে রেন্ডার |  |
| `/user/recover/code` | `userRecoverCode` | অন্য | সার্ভারে রেন্ডার |  |
| `/user/recover/password` | `userRecoverPassword` | অন্য | সার্ভারে রেন্ডার |  |
| `/warranty/rma` | `rma.index` | তালিকা | সার্ভারে রেন্ডার, পেজিনেশন ২৫ | UI-3.L |
| `/warranty/rma/create` | `rma.create` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/warranty/rma/export` | `rma.export` | অন্য | ফাইল ডাউনলোড |  |
| `/warranty/rma/{id}` | `rma.show` | অন্য | সার্ভারে রেন্ডার |  |
| `/warranty/rma/{id}/edit` | `rma.edit` | ফর্ম | সার্ভারে রেন্ডার |  |
| `/warranty/serials` | `serialList` | তালিকা | সার্ভারে রেন্ডার, পেজিনেশন ২৫ | UI-3.L |
| `/warranty/serials/export` | `serials.export` | অন্য | ফাইল ডাউনলোড |  |
