<?php
namespace Tests\Feature;

use App\Models\{AdminUser, BusinessSetup, Product, Supplier};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint04PurchaseUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_bilingual_page_preserves_authoritative_fields_navigation_and_serial_popup(): void
    {
        BusinessSetup::forceCreate(['id'=>1,'businessName'=>'Sprint 04 QA','businessType'=>'computer_shop']);
        $actor=AdminUser::create(['role'=>'admin','businessId'=>1,'fullName'=>'QA','mail'=>'sprint04@example.test','password'=>'unused']);
        $this->actingAs($actor,'admin');
        $supplier=Supplier::create(['businessId'=>1,'name'=>'Sprint 04 Supplier']);
        $first=Product::create(['businessId'=>1,'name'=>'Sprint 04 Product A']);
        $second=Product::create(['businessId'=>1,'name'=>'Sprint 04 Product B']);
        foreach(['en','bn'] as $locale){
            $actor->update(['preferred_locale'=>$locale]);
            $response=$this->get(route('addPurchase'))->assertOk()->assertSee('lang="'.$locale.'"',false);
            $html=$response->getContent();
            $dom=new \DOMDocument();$previous=libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="UTF-8">'.$html);libxml_clear_errors();libxml_use_internal_errors($previous);$xpath=new \DOMXPath($dom);
            foreach(['purchaseDate','supplierName','invoiceData','refData','discountStatus','discountAmount','discountPercent','grandTotal','paidAmount','dueAmount','specialNote'] as $field){
                $this->assertSame(1,$xpath->query('//*[@id="savePurchase"]//*[@name="'.$field.'"]')->length,'Duplicated or missing '.$field);
            }
            $this->assertSame('post',strtolower($xpath->query('//*[@id="savePurchase"]')->item(0)->getAttribute('method')));
            $this->assertSame(route('savePurchase'),$xpath->query('//*[@id="savePurchase"]')->item(0)->getAttribute('action'));
            $this->assertSame(1,$xpath->query('//*[@id="savePurchase"]//*[@name="_token"]')->length);
            $this->assertSame(2,$xpath->query('//*[@id="savePurchase"]//a[@data-purchase-leave]')->length);
            foreach($xpath->query('//*[@id="savePurchase"]//a[@data-purchase-leave]') as $link){$this->assertSame(route('purchaseList'),$link->getAttribute('href'));}
            $this->assertSame(1,$xpath->query('//*[@data-purchase-debug and @hidden]')->length);
            foreach(['purchaseInformationTitle','purchaseAddProductsTitle','purchaseItemsTitle','purchaseSummaryTitle','serialModal','serialNumberBox','saveSerials','add-serial','autoGenerateSerials','purchase-row-template'] as $id){$this->assertSame(1,$xpath->query('//*[@id="'.$id.'"]')->length,'Lost UI contract '.$id);}
            foreach(['purchase_information','add_products','purchased_items','purchase_summary','save_purchase','purchase_empty_title'] as $key){$response->assertSee(__('purchases.'.$key));}
            $this->assertSame(1,$xpath->query('//*[@form="purchaseSerialStaging"]')->length);
            if(getenv('RN_EXPORT_PURCHASE04_FIXTURES')==='1'){
                file_put_contents(storage_path('logs/sprint04-purchase-'.$locale.'.html'),$html);
                $responses=[];foreach([$first,$second] as $product){$responses['/product/details/'.$product->id]=$this->getJson(route('getProductDetails',$product->id))->assertOk()->json();}
                file_put_contents(storage_path('logs/sprint04-purchase-'.$locale.'.json'),json_encode(['supplierId'=>$supplier->id,'productIds'=>[$first->id,$second->id],'responses'=>$responses],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            }
        }
        $this->assertDatabaseCount('purchase_invoices',0);
        $this->assertDatabaseCount('product_serials',0);
    }
}
