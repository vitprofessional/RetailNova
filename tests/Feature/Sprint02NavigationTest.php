<?php

namespace Tests\Feature;

use App\Models\{AdminUser, BusinessSetup};
use App\Services\BusinessNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint02NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_audited_destinations_remain_and_are_unique_with_bilingual_labels(): void
    {
        BusinessSetup::forceCreate(['id'=>1,'businessName'=>'Navigation QA','businessType'=>'computer_shop']);
        $actor=AdminUser::create(['role'=>'super admin','businessId'=>1,'fullName'=>'QA','mail'=>'nav@example.test','password'=>'unused']);
        $this->actingAs($actor,'admin');
        $items=collect(app(BusinessNavigation::class)->groups())->flatMap(fn($group)=>$group['items']);
        $routes=$items->pluck('route')->all();$this->assertSame(count($routes),count(array_unique($routes)));
        $expected=['dashboard','addCustomer','addSupplier','admin.super.users.index','admin.super.subscriptions.plans','admin.super.subscriptions.index','admin.super.stores.index','admin.super.gateways.index',
            'newsale','saleList','returnSaleList','addProduct','productlist','stockProduct','addBrand','addCategory','addProductUnit','barcode.index','addPurchase','purchaseList',
            'provideService','addServiceName','serviceProvideList','rma.index','serialList','quotation.create','quotation.list','damageProduct','damageProductList',
            'account.chart','account.transactions','account.reports','expense.categories','expense.create','expense.list','expense.reports','audits.index','reports.business','reports.sales','reports.purchases','reports.topCustomers','reports.payableReceivable','reports.stock','documentation.index','addBusinessSetupPage','business.locations','sales.settings.edit','returnPurchaseList'];
        foreach($expected as $route){$this->assertContains($route,$routes);$this->assertTrue(\Route::has($route),$route);}
        foreach(['admin','gm'] as $role){$actor->update(['role'=>$role]);$routes=collect(app(BusinessNavigation::class)->groups())->flatMap(fn($group)=>$group['items'])->pluck('route');$this->assertContains('admin.manage.users.index',$routes);}
        $actor->update(['role'=>'admin']);
        foreach(['en','bn'] as $locale){
            $actor->update(['preferred_locale'=>$locale]);$page=$this->get(route('purchaseList'))->assertOk();
            foreach(app(BusinessNavigation::class)->groups() as $group){$page->assertSee(__('navigation.'.$group['key']));foreach($group['items'] as $item){$this->assertTrue(app('translator')->hasForLocale('navigation.'.$item['key'],$locale));}}
            $page->assertSee($locale==='bn'?'ক্রয়ের তালিকা':'Purchase History')->assertDontSee('Naw Parchase')->assertDontSee('Parchase List');
        }
    }

    public function test_role_and_business_type_visibility_preserves_settings_and_module_boundaries(): void
    {
        BusinessSetup::forceCreate(['id'=>1,'businessName'=>'Computer','businessType'=>'computer_shop']);
        BusinessSetup::forceCreate(['id'=>2,'businessName'=>'Grocery','businessType'=>'grocery_shop']);
        $actor=AdminUser::create(['role'=>'admin','businessId'=>1,'fullName'=>'QA','mail'=>'roles@example.test','password'=>'unused']);$this->actingAs($actor,'admin');
        foreach(['admin','superadmin','super admin','GM','storemanager','salesmanager'] as $role){
            $actor->update(['role'=>$role]);$groups=app(BusinessNavigation::class)->groups();$items=collect($groups)->flatMap(fn($group)=>$group['items'])->pluck('route');
            $this->assertSame($actor->canManageSalesSettings(),$items->contains('sales.settings.edit'),$role);
            $this->assertSame(!in_array(strtolower($role),['storemanager','salesmanager']),$items->contains('reports.stock'),$role);
            $this->assertSame(strtolower($role)==='super admin',$items->contains('admin.super.stores.index'),$role);
            $this->assertContains('provideService',$items);$this->assertContains('quotation.list',$items);
        }
        $actor->update(['businessId'=>2]);$items=collect(app(BusinessNavigation::class)->groups())->flatMap(fn($group)=>$group['items'])->pluck('route');
        foreach(['provideService','rma.index','serialList','quotation.list'] as $route){$this->assertNotContains($route,$items);}
        $this->assertContains('returnPurchaseList',$items);$this->assertContains('returnSaleList',$items);
    }

    public function test_invoice_and_settings_navigation_expand_and_highlight_correctly(): void
    {
        BusinessSetup::forceCreate(['id'=>1,'businessName'=>'Navigation QA','businessType'=>'computer_shop']);
        $actor=AdminUser::create(['role'=>'admin','businessId'=>1,'fullName'=>'QA','mail'=>'active@example.test','password'=>'unused']);$this->actingAs($actor,'admin');
        foreach(['purchaseList'=>'parchase','sales.settings.edit'=>'settings','language.settings.edit'=>'settings'] as $route=>$parent){
            $page=$this->get(route($route))->assertOk();$doc=new \DOMDocument;@$doc->loadHTML('<?xml encoding="UTF-8">'.$page->getContent());$xpath=new \DOMXPath($doc);
            $this->assertStringContainsString('show',$xpath->query('//*[@id="'.$parent.'"]')->item(0)->getAttribute('class'));
            $link=$xpath->query('//*[@id="'.$parent.'"]//a[@href="'.route($route).'" and @aria-current="page"]');$this->assertCount(1,$link);
            $this->assertCount(1,$xpath->query('//*[@id="settings"]'));
        }
    }

    public function test_localized_purchase_and_customer_browser_fixtures(): void
    {
        BusinessSetup::forceCreate(['id'=>1,'businessName'=>'QA only','businessType'=>'computer_shop']);
        $actor=AdminUser::create(['role'=>'admin','businessId'=>1,'fullName'=>'QA','mail'=>'fixtures@example.test','password'=>'unused']);$this->actingAs($actor,'admin');
        $supplier=\App\Models\Supplier::create(['businessId'=>1,'name'=>'QA Supplier']);$product=\App\Models\Product::create(['businessId'=>1,'name'=>'QA Product']);
        $invoice=app(\App\Services\PurchaseWorkflow::class)->save(['businessId'=>1,'supplierName'=>$supplier->id,'purchaseDate'=>'2026-10-12','invoiceData'=>'QA-Invoice','rows'=>['qa'=>['product_id'=>$product->id,'qty'=>10,'buy_price'=>12,'serials'=>['QA-SERIAL']]]]);
        $legacy=$invoice->items->first()->replicate();$legacy->purchase_invoice_id=null;$legacy->invoice='QA-LEGACY';$legacy->save();
        foreach(['en','bn'] as $locale){
            $actor->update(['preferred_locale'=>$locale]);
            foreach(['purchase-new'=>route('addPurchase'),'purchase-list'=>route('purchaseList'),'purchase-view'=>route('purchase.invoice.show',$invoice->id),'purchase-edit'=>route('purchase.invoice.edit',$invoice->id),'customer'=>route('addCustomer')] as $name=>$url){
                $page=$this->get($url)->assertOk()->assertSee('lang="'.$locale.'"',false);
                if(getenv('RN_EXPORT_BROWSER_FIXTURES')==='1'){file_put_contents(storage_path('framework/sprint02-'.$name.'-'.$locale.'.html'),$page->getContent());}
            }
        }
        $this->assertEquals(10,$invoice->items->first()->stock->currentStock);
        $this->assertDatabaseHas('product_serials',['serialNumber'=>'QA-SERIAL']);
    }
}
