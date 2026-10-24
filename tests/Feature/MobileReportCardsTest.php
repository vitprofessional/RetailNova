<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\PurchaseProduct;
use App\Models\SaleProduct;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileReportCardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        BusinessSetup::forceCreate(['id' => 1, 'businessName' => 'Reports Mobile Shop']);
        $user = AdminUser::create([
            'fullName' => 'Reports Owner',
            'mail' => 'reports-mobile@example.test',
            'password' => 'secret',
            'role' => 'admin',
            'businessId' => 1,
        ]);

        $this->actingAs($user, 'admin')->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 14; Mobile) AppleWebKit/537.36 Chrome/129.0 Mobile Safari/537.36',
        ]);
    }

    public function test_expense_report_uses_real_grouped_data_and_mobile_report_cards(): void
    {
        $category = ExpenseCategory::forceCreate([
            'name' => 'Mobile Test Rent', 'businessId' => 1, 'is_active' => true,
        ]);
        ExpenseEntry::forceCreate([
            'businessId' => 1,
            'expense_date' => now()->toDateString(),
            'category_id' => $category->id,
            'amount' => 275,
            'payment_method' => 'cash',
            'reference_no' => 'MOBILE-REPORT-1',
            'created_by' => 1,
            'created_by_type' => 'admin',
        ]);

        $this->get(route('expense.reports', ['group_by' => 'category']))
            ->assertOk()
            ->assertSee('rn-report-page', false)
            ->assertSee('rn-report-table', false)
            ->assertSee('Mobile Test Rent')
            ->assertSee('৳275')
            ->assertDontSee('Mr Green')
            ->assertDontSee('01880375361');
    }

    public function test_report_table_card_script_and_responsive_rules_are_shared(): void
    {
        $this->get(route('reports.index'))->assertOk()->assertSee('rn-m-report-hub', false);

        $script = file_get_contents(resource_path('views/components/mobile/report-card-script.blade.php'));
        $css = file_get_contents(public_path('css/rn-m-components.css'));

        $this->assertStringContainsString("cell.dataset.label = headings[index]", $script);
        $this->assertStringContainsString('.rn-report-table tbody tr {', $css);
        $this->assertStringContainsString('content: attr(data-label)', $css);
    }

    public function test_mobile_sales_report_uses_one_line_periods_and_filter_sheet_trigger(): void
    {
        $response = $this->get(route('reports.sales'))->assertOk()
            ->assertSee('data-rn-report-period-tools', false)
            ->assertSee('data-rn-report-filter', false)
            ->assertSee('data-rn-report-period="today"', false)
            ->assertSee('data-rn-report-period="week"', false)
            ->assertSee('data-rn-report-period="month"', false)
            ->assertSee('data-rn-report-period="custom"', false);

        $css = file_get_contents(public_path('css/rn-m-components.css'));
        $script = file_get_contents(resource_path('views/components/mobile/report-card-script.blade.php'));
        $this->assertStringContainsString('white-space: nowrap;', $css);
        $this->assertStringContainsString('position: fixed;', $css);
        $this->assertStringContainsString('data-rn-report-filter', $script);
        $this->assertStringContainsString('data-rn-filter-apply', $script);
        $this->assertStringContainsString('aria-modal="true"', $script);
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $this->assertSame(1, (new \DOMXPath($document))->query('//*[@data-rn-report-filter]')->length);
    }

    public function test_mobile_sales_report_localizes_transaction_date_and_profit_currency(): void
    {
        auth('admin')->user()->forceFill(['preferred_locale' => 'bn'])->save();
        $saleDate = now()->toDateString();
        SaleProduct::forceCreate([
            'businessId' => 1, 'date' => $saleDate, 'invoice' => 'MOBILE-REPORT-LOCALE-1',
            'totalSale' => 300, 'grandTotal' => 321, 'curDue' => 0,
        ]);

        $this->get(route('reports.sales'))
            ->assertOk()
            ->assertSee(\App\Support\MobileDateTime::date($saleDate))
            ->assertSee(\App\Support\LocalizedNumber::money(21))
            ->assertDontSee('৳0.00');
    }

    public function test_business_overview_groups_monthly_sales_on_sqlite_without_cross_business_rows(): void
    {
        BusinessSetup::forceCreate(['id' => 2, 'businessName' => 'Other Reports Shop']);
        SaleProduct::forceCreate([
            'businessId' => 1, 'date' => now()->toDateString(), 'invoice' => 'MOBILE-OVERVIEW-1',
            'totalSale' => '300', 'grandTotal' => '321',
        ]);
        SaleProduct::forceCreate([
            'businessId' => 2, 'date' => now()->toDateString(), 'invoice' => 'OTHER-OVERVIEW-1',
            'totalSale' => '900', 'grandTotal' => '999',
        ]);

        $this->get(route('reports.business'))
            ->assertOk()
            ->assertSee('rn-report-mobile-overview', false)
            ->assertSee('rn-report-mobile-kpis', false)
            ->assertSee('৳321')
            ->assertDontSee('999.00');
    }

    public function test_mobile_business_overview_has_real_weekly_sales_top_products_periods_and_export(): void
    {
        BusinessSetup::forceCreate(['id'=>2,'businessName'=>'Other Reports Shop']);
        $supplier=Supplier::forceCreate(['name'=>'Report Supplier','businessId'=>1]);
        $product=Product::forceCreate(['name'=>'Seven Day Product','businessId'=>1]);
        $foreignProduct=Product::forceCreate(['name'=>'Foreign Product','businessId'=>2]);
        $lot=PurchaseProduct::forceCreate(['businessId'=>1,'supplier'=>(string)$supplier->id,'productName'=>(string)$product->id,
            'invoice'=>'REPORT-LOT','purchase_date'=>now()->toDateString(),'qty'=>10,'buyPrice'=>50,'salePriceExVat'=>80]);
        $sale=SaleProduct::forceCreate(['businessId'=>1,'date'=>now()->toDateString(),'invoice'=>'OVERVIEW-SELL-1',
            'totalSale'=>160,'grandTotal'=>160,'curDue'=>40,'created_at'=>now(),'updated_at'=>now()]);
        InvoiceItem::forceCreate(['businessId'=>1,'saleId'=>$sale->id,'purchaseId'=>$lot->id,'qty'=>2,
            'totalSale'=>160,'totalPurchase'=>100,'profitTotal'=>60]);
        $foreignSale=SaleProduct::forceCreate(['businessId'=>2,'date'=>now()->toDateString(),'invoice'=>'FOREIGN-SELL-1',
            'totalSale'=>900,'grandTotal'=>900,'curDue'=>900,'created_at'=>now(),'updated_at'=>now()]);
        InvoiceItem::forceCreate(['businessId'=>2,'saleId'=>$foreignSale->id,'purchaseId'=>$lot->id,'qty'=>9,
            'totalSale'=>900,'totalPurchase'=>450,'profitTotal'=>450]);
        $category=ExpenseCategory::forceCreate(['name'=>'Overview Expense','businessId'=>1,'is_active'=>true]);
        ExpenseEntry::forceCreate(['businessId'=>1,'expense_date'=>now()->toDateString(),'category_id'=>$category->id,
            'amount'=>25,'payment_method'=>'cash','created_by'=>1,'created_by_type'=>'admin']);

        $this->get(route('reports.business',['period'=>'week']))
            ->assertOk()->assertSee('rn-report-mobile-overview',false)
            ->assertSee('rn-report-mobile-kpis',false)->assertSee('data-rn-report-download',false)
            ->assertSee('Seven Day Product')->assertSee('৳160')->assertSee('৳25')->assertSee('৳40')
            ->assertDontSee('Foreign Product')->assertDontSee('900.00')
            ->assertSee(__('reports.period_today'))->assertSee(__('reports.period_custom'));
    }

    public function test_mobile_business_overview_uses_bangla_labels_when_bangla_is_selected(): void
    {
        auth('admin')->user()->forceFill(['preferred_locale' => 'bn'])->save();

        $this->get(route('reports.business', ['period' => 'week']))
            ->assertOk()
            ->assertSee('ব্যবসার ওভারভিউ')
            ->assertSee('রিপোর্ট ডাউনলোড')
            ->assertSee('গত ৭ দিনের বিক্রি')
            ->assertSee('এই সময়ে কোনো পণ্য বিক্রি হয়নি।')
            ->assertDontSee('Business Overview')
            ->assertDontSee('Download report');
    }
}
