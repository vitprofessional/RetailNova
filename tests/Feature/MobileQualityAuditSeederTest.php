<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Models\Customer;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseProduct;
use App\Models\PurchaseReturn;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\ReturnPurchaseItem;
use App\Models\ReturnSaleItem;
use App\Models\SaleProduct;
use App\Models\SaleReturn;
use App\Models\ServiceInvoice;
use App\Models\ServiceInvoiceItem;
use App\Models\Supplier;
use Database\Seeders\MobileQualityAuditSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileQualityAuditSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_documents_are_seeded_idempotently_and_admin_name_is_localized(): void
    {
        $business = BusinessSetup::forceCreate(['businessName' => 'Audit Fixture Shop', 'businessType' => 'computer_shop']);
        AdminUser::forceCreate([
            'fullName' => 'Audit Admin 7', 'mail' => 'audit-seeder@example.test', 'password' => bcrypt('test-only'),
            'role' => 'admin', 'businessId' => $business->id,
        ]);
        putenv('RN_AUDIT_EMAIL=audit-seeder@example.test');

        try {
            $firstSeeder = new MobileQualityAuditSeeder();
            $firstSeeder->run();
            $firstIds = $firstSeeder->documentIds();

            $secondSeeder = new MobileQualityAuditSeeder();
            $secondSeeder->run();

            $this->assertSame($firstIds, $secondSeeder->documentIds());
            $this->assertCount(8, $firstIds);
            $this->assertSame('পরীক্ষা প্রশাসক', AdminUser::query()->where('businessId', $business->id)->value('fullName'));
            $this->assertSame(1, Customer::query()->where('businessId', $business->id)->count());
            $this->assertSame(1, Supplier::query()->where('businessId', $business->id)->count());
            $this->assertSame(1, Product::query()->where('businessId', $business->id)->count());
            $this->assertSame(1, SaleProduct::query()->where('businessId', $business->id)->count());
            $this->assertSame(1, InvoiceItem::query()->where('businessId', $business->id)->count());
            $this->assertSame(1, PurchaseProduct::query()->where('businessId', $business->id)->count());
            $this->assertSame(1, PurchaseInvoice::query()->where('businessId', $business->id)->count());
            $this->assertSame(1, Quotation::query()->where('businessId', $business->id)->count());
            $this->assertSame(1, QuotationItem::query()->where('businessId', $business->id)->count());
            $this->assertSame(1, ServiceInvoice::query()->where('businessId', $business->id)->count());
            $this->assertSame(1, ServiceInvoiceItem::query()->where('businessId', $business->id)->count());
            $this->assertSame(1, SaleReturn::query()->where('businessId', $business->id)->count());
            $this->assertSame(1, ReturnSaleItem::query()->where('businessId', $business->id)->count());
            $this->assertSame(1, PurchaseReturn::query()->where('businessId', $business->id)->count());
            $this->assertSame(1, ReturnPurchaseItem::query()->where('businessId', $business->id)->count());
        } finally {
            putenv('RN_AUDIT_EMAIL');
        }
    }
}
