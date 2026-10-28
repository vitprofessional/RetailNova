<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSaleFixtures;
use Tests\TestCase;

class MobileReturnCreateFlowTest extends TestCase
{
    use BuildsSaleFixtures;
    use RefreshDatabase { refreshDatabase as private refreshIsolatedDatabase; }

    protected function refreshDatabase()
    {
        if (! app()->environment('testing') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Mobile return tests require testing + SQLite :memory:.');
        }
        $this->refreshIsolatedDatabase();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14; Mobile) AppleWebKit/537.36 Chrome/129.0 Mobile Safari/537.36');
    }

    public function test_mobile_sale_return_creation_finishes_on_the_return_list(): void
    {
        $inventory = $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');
        $sale = $this->sell($inventory, 1);

        $this->post(route('saleReturnSave'), [
            'customerId' => $sale->customerId,
            'saleId' => [$sale->id],
            'productId' => [$inventory['product']->id],
            'purchaseId' => [$inventory['purchase']->id],
            'totalQty' => [1],
            'adjustAmount' => 0,
        ])->assertRedirect(route('returnSaleList'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sale_returns', ['saleId' => $sale->id]);
    }

    public function test_mobile_purchase_return_creation_finishes_on_the_return_list(): void
    {
        $inventory = $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');

        $this->post(route('purchaseReturnSave'), [
            'purchaseId' => $inventory['purchase']->id,
            'supplierId' => $inventory['supplier']->id,
            'productId' => $inventory['product']->id,
            'returnQty' => 1,
            'serialIds' => [$inventory['serials'][0]->id],
        ])->assertRedirect(route('returnPurchaseList'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('purchase_returns', ['purchaseId' => $inventory['purchase']->id]);
    }
}
