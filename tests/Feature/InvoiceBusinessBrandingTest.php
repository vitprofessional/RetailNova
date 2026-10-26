<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoiceBusinessBrandingTest extends TestCase
{
    use RefreshDatabase;

    private array $temporaryLogos = [];
    private bool $createdLogoDirectory = false;

    protected function tearDown(): void
    {
        foreach ($this->temporaryLogos as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $directory = public_path('uploads/business');
        if ($this->createdLogoDirectory && is_dir($directory) && count(glob($directory . '/*') ?: []) === 0) {
            @rmdir($directory);
        }

        parent::tearDown();
    }

    public function test_invoice_formats_use_the_business_owned_by_the_sale(): void
    {
        [$logoOne, $logoTwo] = $this->createTemporaryLogos();
        $businessOne = $this->business('Northwind Parts & Service Center', $logoOne, '10 North Road', '01700000001', 'northwind@example.test');
        $businessTwo = $this->business('Delta Retail Group', $logoTwo, '22 South Avenue', '01800000002', 'delta@example.test');
        $actorOne = $this->actor((int) $businessOne->id, 'sales-one@example.test');
        $actorTwo = $this->actor((int) $businessTwo->id, 'sales-two@example.test');
        $saleOne = $this->sale((int) $businessOne->id, (int) $actorOne->id, 'NW-1001');
        $saleTwo = $this->sale((int) $businessTwo->id, (int) $actorTwo->id, 'DL-2001');

        $this->actingAs($actorOne, 'admin');
        foreach (['a4', 'thermal80', 'thermal58'] as $printer) {
            $response = $this->get(route('invoiceGenerate', ['id' => $saleOne->id, 'printer' => $printer]));
            $response->assertOk();
            $response->assertViewHas('business', fn ($business) => $business && (int) $business->id === (int) $businessOne->id);
            $response->assertSee('Northwind Parts & Service Center');
            $response->assertSee('10 North Road')->assertSee('01700000001')->assertSee('northwind@example.test');
            $response->assertSee('/uploads/business/' . $logoOne);
            $response->assertDontSee('Delta Retail Group')->assertDontSee('01800000002')->assertDontSee('delta@example.test');
            $response->assertDontSee('Computer Care')->assertDontSee('0123456789');
        }

        $this->get(route('invoicePage', ['id' => $saleOne->id]))->assertOk()->assertSee('Northwind Parts & Service Center')->assertSee('/uploads/business/' . $logoOne);
        $this->followingRedirects()->get(route('invoiceThermalPrint', ['id' => $saleOne->id, 'printer' => 'thermal58']))
            ->assertOk()->assertSee('Northwind Parts & Service Center')->assertSee('thermal58')->assertSee('/uploads/business/' . $logoOne);

        $this->actingAs($actorTwo, 'admin');
        $this->get(route('invoiceGenerate', ['id' => $saleTwo->id]))->assertOk()
            ->assertSee('Delta Retail Group')->assertSee('22 South Avenue')->assertSee('01800000002')
            ->assertSee('delta@example.test')->assertSee('/uploads/business/' . $logoTwo)
            ->assertDontSee('Northwind Parts & Service Center');
    }

    public function test_business_users_cannot_view_foreign_or_unowned_invoices(): void
    {
        $businessOne = $this->business('Owner One', null, 'One Road', '01711111111', null);
        $businessTwo = $this->business('Owner Two', null, 'Two Road', '01822222222', null);
        $actor = $this->actor((int) $businessOne->id, 'owner-one@example.test');
        $foreignSale = $this->sale((int) $businessTwo->id, (int) $actor->id, 'FOREIGN-1');
        $unownedSale = $this->sale(null, (int) $actor->id, 'LEGACY-NULL-1');

        $this->actingAs($actor, 'admin');
        $this->assertDenied($this->getJson(route('invoiceGenerate', ['id' => $foreignSale->id])))->assertDontSee('FOREIGN-1');
        $this->assertDenied($this->getJson(route('invoicePage', ['id' => $foreignSale->id])))->assertDontSee('FOREIGN-1');
        $this->assertDenied($this->getJson(route('invoiceThermalPrint', ['id' => $foreignSale->id])))->assertDontSee('FOREIGN-1');
        $this->assertDenied($this->getJson(route('invoiceGenerate', ['id' => $unownedSale->id])))->assertDontSee('LEGACY-NULL-1');
    }

    public function test_configured_logo_falls_back_safely_when_its_file_is_missing(): void
    {
        $business = $this->business('Fallback Shop', 'missing-test-logo.png', null, null, null);
        $actor = $this->actor((int) $business->id, 'fallback@example.test');
        $sale = $this->sale((int) $business->id, (int) $actor->id, 'FALLBACK-1');

        $this->assertNull($business->invoiceLogoUrl());
        $this->assertStringContainsString('/logo.png', asset('logo.png'));
        $this->actingAs($actor, 'admin');
        $this->get(route('invoiceGenerate', ['id' => $sale->id]))
            ->assertOk()->assertSee('Fallback Shop')->assertSee('logo.png')
            ->assertDontSee('0123456789')->assertDontSee('info@computercare.com');
    }

    public function test_business_admin_can_open_another_employee_invoice_and_sale_edit(): void
    {
        $business = $this->business('Admin Access Shop', null, null, null, null);
        $admin = $this->actor((int) $business->id, 'shop-admin@example.test');
        $admin->update(['role' => 'admin']);
        $employee = $this->actor((int) $business->id, 'shop-employee@example.test');
        $sale = $this->sale((int) $business->id, (int) $employee->id, 'ADMIN-VIEW-1');

        $this->actingAs($admin, 'admin');
        $this->get(route('invoiceGenerate', ['id' => $sale->id]))->assertOk();
        $this->get(route('sale.edit', ['id' => $sale->id]))->assertOk();
    }

    private function business(string $name, ?string $logo, ?string $address, ?string $mobile, ?string $email): BusinessSetup
    {
        return BusinessSetup::create([
            'businessName' => $name,
            'businessLocation' => $address,
            'mobile' => $mobile,
            'email' => $email,
            'businessLogo' => $logo,
        ]);
    }

    private function actor(int $businessId, string $email): AdminUser
    {
        return AdminUser::create([
            'fullName' => 'Invoice Test User',
            'mail' => $email,
            'password' => bcrypt('test-only'),
            'businessId' => $businessId,
            'role' => 'salesmanager',
        ]);
    }

    private function sale(?int $businessId, int $actorId, string $invoice): object
    {
        $id = DB::table('sale_products')->insertGetId([
            'businessId' => $businessId,
            'salespersonId' => $actorId,
            'date' => '2026-10-19',
            'invoice' => $invoice,
            'grandTotal' => '125.00',
            'paidAmount' => '25.00',
            'curDue' => '100.00',
            'status' => 'Ordered',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return \App\Models\SaleProduct::withoutGlobalScopes()->findOrFail($id);
    }

    private function createTemporaryLogos(): array
    {
        $directory = public_path('uploads/business');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
            $this->createdLogoDirectory = true;
        }

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/n8sAAAAASUVORK5CYII=');
        $names = ['invoice-test-' . uniqid('', true) . '-one.png', 'invoice-test-' . uniqid('', true) . '-two.png'];
        foreach ($names as $name) {
            $path = $directory . DIRECTORY_SEPARATOR . $name;
            file_put_contents($path, $png);
            $this->temporaryLogos[] = $path;
        }

        return $names;
    }
}
