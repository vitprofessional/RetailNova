<?php

namespace Tests\Concerns;

use App\Models\{BusinessSetup, InvoiceItem, ProductSerial, SaleProduct};

trait TestsSalesSerialPolicy
{
    private function optional(): void
    {
        BusinessSetup::findOrFail(1)->update(['sales_serial_selection_required' => false]);
    }

    private function existingSale(array $inventory): array
    {
        $this->post(route('saveSale'), $this->payload($inventory))->assertRedirect();
        return [SaleProduct::firstOrFail(), InvoiceItem::firstOrFail()];
    }

    public function test_mandatory_default_rejects_omitted_selection(): void
    {
        $inventory = $this->inventory();
        $this->login();
        $this->assertTrue(BusinessSetup::find(1)->sales_serial_selection_required);
        $payload = $this->payload($inventory);
        unset($payload['serialId']);
        $this->postJson(route('saveSale'), $payload)->assertUnprocessable()->assertJsonValidationErrors('serialId.0');
        $this->assertRolledBack($inventory);
    }

    public function test_optional_selection_still_rejects_partial_duplicate_sold_and_foreign_serials(): void
    {
        $inventory = $this->inventory();
        $other = $this->inventory(2);
        $this->optional();
        $this->login();
        foreach ([[$inventory['serials'][0]->id], [$inventory['serials'][0]->id, $inventory['serials'][0]->id],
            [$other['serials'][0]->id, $other['serials'][1]->id]] as $ids) {
            $payload = $this->payload($inventory);
            $payload['serialId'] = [$ids];
            $this->postJson(route('saveSale'), $payload)->assertUnprocessable()->assertJsonValidationErrors('serialId.0');
            $this->assertRolledBack($inventory);
        }
        $inventory['serials'][0]->update(['saleId' => 999, 'status' => 'sold']);
        $this->postJson(route('saveSale'), $this->payload($inventory))->assertUnprocessable();
        $this->assertSame(999, (int)$inventory['serials'][0]->fresh()->saleId);
        $this->assertDatabaseCount('sale_products', 0);
        $this->assertSame(3, (int)$inventory['stock']->fresh()->currentStock);
    }

    public function test_optional_auto_allocation_excludes_sold_and_unavailable_serials(): void
    {
        $inventory = $this->inventory();
        $this->optional();
        $inventory['serials'][0]->update(['status' => 'reserved']);
        $this->login();
        $payload = $this->payload($inventory);
        unset($payload['serialId']);
        $this->post(route('saveSale'), $payload)->assertRedirect();
        $this->assertNull($inventory['serials'][0]->fresh()->saleId);
        $this->assertSame(2, ProductSerial::where('status', 'sold')->count());
        $this->getJson(route('ajax.purchase.serials.public', $inventory['purchase']->id))->assertOk()->assertJsonCount(0, 'serials');
    }

    public function test_optional_insufficient_serial_inventory_rolls_back(): void
    {
        $inventory = $this->inventory();
        $this->optional();
        $inventory['serials'][0]->update(['saleId' => 999, 'status' => 'available']);
        $inventory['serials'][1]->update(['status' => 'reserved']);
        $this->login();
        $payload = $this->payload($inventory);
        unset($payload['serialId']);
        $this->postJson(route('saveSale'), $payload)->assertUnprocessable();
        $this->assertDatabaseCount('sale_products', 0);
        $this->assertDatabaseCount('invoice_items', 0);
        $this->assertSame(3, (int)$inventory['stock']->fresh()->currentStock);
        $this->assertNull($inventory['serials'][2]->fresh()->saleId);
    }

    public function test_optional_insufficient_stock_rolls_back_auto_allocation(): void
    {
        $inventory = $this->inventory();
        $this->optional();
        $inventory['stock']->update(['currentStock' => 1]);
        $this->login();
        $payload = $this->payload($inventory);
        unset($payload['serialId']);
        $this->post(route('saveSale'), $payload)->assertRedirect();
        $this->assertDatabaseCount('sale_products', 0);
        $this->assertSame(1, (int)$inventory['stock']->fresh()->currentStock);
        $this->assertSame(0, ProductSerial::whereNotNull('saleId')->count());
    }

    public function test_optional_nonserialized_sale_preserves_stock_and_totals(): void
    {
        $inventory = $this->inventory(1, false);
        $this->optional();
        $this->login();
        $this->post(route('saveSale'), $this->payload($inventory))->assertRedirect();
        $this->assertSame(1, (int)$inventory['stock']->fresh()->currentStock);
        $this->assertEquals(295, SaleProduct::firstOrFail()->grandTotal);
        $this->assertDatabaseCount('product_serials', 0);
    }

    public function test_sparse_serial_row_keys_are_not_shifted(): void
    {
        $plain = $this->inventory(1, false);
        $serial = $this->inventory();
        $this->login();
        $payload = $this->payload($plain, 1);
        $payload['purchaseData'][] = $serial['purchase']->id;
        $payload['qty'][] = 2;
        $payload['salePrice'][] = 150;
        $payload['buyPrice'][] = 100;
        $payload['serialId'] = [1 => [$serial['serials'][0]->id, $serial['serials'][1]->id]];
        $this->post(route('saveSale'), $payload)->assertRedirect();
        $this->assertDatabaseCount('invoice_items', 2);
        $this->assertSame(2, ProductSerial::where('status', 'sold')->count());
    }

    public function test_current_business_policy_is_used_when_form_setting_changes(): void
    {
        $inventory = $this->inventory();
        $this->optional();
        $this->login();
        $this->getJson(route('ajax.purchase.serials.public', $inventory['purchase']->id))
            ->assertJsonPath('selection_required', false);
        $payload = $this->payload($inventory);
        unset($payload['serialId']);
        BusinessSetup::find(1)->update(['sales_serial_selection_required' => true]);
        $this->postJson(route('saveSale'), $payload)->assertUnprocessable()->assertJsonValidationErrors('serialId.0');
        $this->assertRolledBack($inventory);
        $this->optional();
        $this->post(route('saveSale'), $payload)->assertRedirect();
        $this->assertDatabaseCount('sale_products', 1);
    }

    public function test_latest_other_business_setting_does_not_override_sale_policy(): void
    {
        $inventory = $this->inventory();
        BusinessSetup::create(['id' => 2, 'businessName' => 'Other', 'sales_serial_selection_required' => false]);
        $this->login();
        $payload = $this->payload($inventory);
        unset($payload['serialId']);
        $this->postJson(route('saveSale'), $payload)->assertUnprocessable();
        $this->assertRolledBack($inventory);
    }

    public function test_salesmanager_cannot_sell_other_business_inventory_even_when_optional(): void
    {
        $inventory = $this->inventory(2);
        BusinessSetup::find(2)->update(['sales_serial_selection_required' => false]);
        $this->actingAs($this->actor('salesmanager', 1), 'admin');
        $payload = $this->payload($inventory);
        unset($payload['serialId']);
        $payload['businessId'] = 2;
        $this->postJson(route('saveSale'), $payload)->assertUnprocessable();
        $this->assertDatabaseCount('sale_products', 0);
        $this->assertSame(3, (int)$inventory['stock']->fresh()->currentStock);
    }

    public function test_backorders_do_not_consume_stock_or_allocate_serials_in_either_policy(): void
    {
        $inventory = $this->inventory();
        $this->login();
        foreach ([true, false] as $required) {
            BusinessSetup::find(1)->update(['sales_serial_selection_required' => $required]);
            $payload = $this->payload($inventory, 4);
            unset($payload['serialId']);
            $payload['backorder'] = [$inventory['purchase']->id];
            $this->post(route('saveSale'), $payload)->assertRedirect();
        }
        $this->assertDatabaseCount('sale_products', 2);
        $this->assertSame(3, (int)$inventory['stock']->fresh()->currentStock);
        $this->assertSame(0, ProductSerial::whereNotNull('saleId')->count());
        $item = InvoiceItem::firstOrFail();
        $this->post(route('sale.items.update', $item->saleId), ['items' => [$item->id => ['qty' => 6]]])->assertRedirect();
        $this->assertEquals(6, $item->fresh()->qty);
        $this->assertSame(3, (int)$inventory['stock']->fresh()->currentStock);
    }

    public function test_optional_edit_increase_reduce_and_remove_reconcile_serials_and_stock(): void
    {
        $inventory = $this->inventory();
        $this->login();
        [$sale, $item] = $this->existingSale($inventory);
        $this->optional();
        foreach ([3, 1, 0] as $qty) {
            $this->post(route('sale.items.update', $sale->id), ['items' => [$item->id => ['qty' => $qty]]])->assertRedirect();
            $this->assertSame(3 - $qty, (int)$inventory['stock']->fresh()->currentStock);
            $this->assertSame($qty, ProductSerial::where('saleId', $sale->id)->count());
            $this->assertEquals(150 * $qty, $sale->fresh()->totalSale);
        }
        $this->assertDatabaseCount('invoice_items', 0);
        $this->assertSame(3, ProductSerial::where('status', 'available')->count());
    }

    public function test_mandatory_edit_can_retain_assignments_but_increase_requires_selection(): void
    {
        $inventory = $this->inventory();
        $this->login();
        [$sale, $item] = $this->existingSale($inventory);
        $this->post(route('sale.items.update', $sale->id), ['items' => [$item->id => ['qty' => 2]]])->assertRedirect();
        $this->postJson(route('sale.items.update', $sale->id), ['items' => [$item->id => ['qty' => 3]]])
            ->assertUnprocessable()->assertJsonValidationErrors('serialIdByItem.'.$item->id);
        $this->assertEquals(2, $item->fresh()->qty);
        $this->assertSame(1, (int)$inventory['stock']->fresh()->currentStock);
        $this->post(route('sale.items.update', $sale->id), ['items' => [$item->id => ['qty' => 3]],
            'serialIdByItem' => [$item->id => array_column(array_map(fn($s) => $s->toArray(), $inventory['serials']), 'id')]])->assertRedirect();
        $this->assertSame(3, ProductSerial::where('saleId', $sale->id)->count());
    }

    public function test_invalid_edit_serials_roll_back_stock_and_existing_assignments(): void
    {
        $inventory = $this->inventory();
        $other = $this->inventory(2);
        $this->login();
        [$sale, $item] = $this->existingSale($inventory);
        $this->optional();
        foreach ([[$inventory['serials'][0]->id, $inventory['serials'][0]->id],
            [$other['serials'][0]->id, $other['serials'][1]->id]] as $ids) {
            $this->postJson(route('sale.items.update', $sale->id), ['items' => [$item->id => ['qty' => 2]],
                'serialIdByItem' => [$item->id => $ids]])->assertUnprocessable();
            $this->assertSame(2, ProductSerial::where('saleId', $sale->id)->count());
            $this->assertSame(1, (int)$inventory['stock']->fresh()->currentStock);
        }
    }

    public function test_optional_edit_adds_new_serialized_purchase_with_automatic_allocation(): void
    {
        $inventory = $this->inventory();
        $second = $this->inventory();
        $this->login();
        [$sale, $item] = $this->existingSale($inventory);
        $this->optional();
        $this->post(route('sale.items.update', $sale->id), ['add' => ['purchaseId' => $second['purchase']->id,
            'qty' => 1, 'salePrice' => 150]])->assertRedirect();
        $this->assertDatabaseCount('invoice_items', 2);
        $this->assertSame(3, ProductSerial::where('saleId', $sale->id)->count());
        $this->assertSame(2, (int)$second['stock']->fresh()->currentStock);
    }

    public function test_mandatory_edit_add_rejects_missing_serials_and_rolls_back_stock(): void
    {
        $inventory = $this->inventory();
        $second = $this->inventory();
        $this->login();
        [$sale, $item] = $this->existingSale($inventory);
        $this->postJson(route('sale.items.update', $sale->id), ['add' => ['purchaseId' => $second['purchase']->id,
            'qty' => 1, 'salePrice' => 150]])->assertUnprocessable();
        $this->assertDatabaseCount('invoice_items', 1);
        $this->assertSame(3, (int)$second['stock']->fresh()->currentStock);
        $this->assertSame(2, ProductSerial::where('saleId', $sale->id)->count());
    }

    public function test_settings_are_admin_only_and_business_scoped(): void
    {
        $this->inventory();
        $this->inventory(2);
        $this->actor('superadmin'); // Ordinary staff cases must not be the legacy first-created owner.
        $this->getJson(route('sales.settings.edit'))->assertUnauthorized();
        foreach (['gm', 'storemanager', 'salesmanager'] as $role) {
            $this->actingAs($this->actor($role), 'admin');
            $this->getJson(route('sales.settings.edit'))->assertForbidden();
            $this->postJson(route('sales.settings.update'), ['businessId' => 1, 'sales_serial_selection_required' => false])->assertForbidden();
        }
        $this->actingAs($this->actor('admin'), 'admin');
        $this->get(route('sales.settings.edit'))->assertOk()->assertSee('Require manual serial selection');
        $this->post(route('sales.settings.update'), ['businessId' => 1, 'sales_serial_selection_required' => false])->assertRedirect();
        $this->assertFalse(BusinessSetup::find(1)->sales_serial_selection_required);
        $this->postJson(route('sales.settings.update'), ['businessId' => 2, 'sales_serial_selection_required' => false])->assertNotFound();
        $this->getJson(route('sales.settings.edit', ['businessId' => 2]))->assertNotFound();
        $this->assertTrue(BusinessSetup::find(2)->sales_serial_selection_required);
        $this->actingAs($this->actor('superadmin'), 'admin');
        $this->post(route('sales.settings.update'), ['businessId' => 2, 'sales_serial_selection_required' => false])->assertRedirect();
        $this->assertFalse(BusinessSetup::find(2)->sales_serial_selection_required);
    }

    public function test_explicit_superadmin_retains_platform_settings_access(): void
    {
        $this->inventory(2);
        $this->actingAs($this->actor('superadmin', 1), 'admin');
        $this->post(route('sales.settings.update'), ['businessId' => 2, 'sales_serial_selection_required' => false])->assertRedirect();
        $this->assertFalse(BusinessSetup::find(2)->sales_serial_selection_required);
    }

    public function test_settings_reject_invalid_boolean_without_changing_data(): void
    {
        $this->inventory();
        $this->actingAs($this->actor('superadmin'), 'admin');
        $this->postJson(route('sales.settings.update'), ['businessId' => 1, 'sales_serial_selection_required' => 'anything'])
            ->assertUnprocessable()->assertJsonValidationErrors('sales_serial_selection_required');
        $this->assertTrue(BusinessSetup::find(1)->sales_serial_selection_required);
    }

    public function test_optional_manual_selection_is_preserved_instead_of_auto_replaced(): void
    {
        $inventory = $this->inventory();
        $this->optional();
        $this->login();
        $payload = $this->payload($inventory, 1);
        $payload['serialId'] = [[$inventory['serials'][2]->id]];
        $this->post(route('saveSale'), $payload)->assertRedirect();
        $this->assertNull($inventory['serials'][0]->fresh()->saleId);
        $this->assertSame(SaleProduct::firstOrFail()->id, (int)$inventory['serials'][2]->fresh()->saleId);
    }

    public function test_duplicate_serialized_purchase_rows_are_rejected_atomically(): void
    {
        $inventory = $this->inventory();
        $this->optional();
        $this->login();
        $payload = $this->payload($inventory, 1);
        unset($payload['serialId']);
        foreach (['purchaseData', 'qty', 'salePrice', 'buyPrice'] as $key) { $payload[$key][] = $payload[$key][0]; }
        $this->postJson(route('saveSale'), $payload)->assertUnprocessable()->assertJsonValidationErrors('purchaseData.1');
        $this->assertRolledBack($inventory);
    }

    public function test_optional_later_row_failure_rolls_back_automatic_assignments(): void
    {
        $first = $this->inventory();
        $second = $this->inventory();
        $this->optional();
        $second['stock']->update(['currentStock' => 0]);
        $this->login();
        $payload = $this->payload($first);
        unset($payload['serialId']);
        foreach (['purchaseData' => $second['purchase']->id, 'qty' => 2, 'salePrice' => 150, 'buyPrice' => 100] as $key => $value) {
            $payload[$key][] = $value;
        }
        $this->post(route('saveSale'), $payload)->assertRedirect();
        $this->assertRolledBack($first);
        $this->assertSame(0, ProductSerial::whereNotNull('saleId')->count());
        $this->assertSame(0, (int)$second['stock']->fresh()->currentStock);
    }

    public function test_serialized_product_lot_without_serial_records_is_not_treated_as_plain_stock(): void
    {
        $inventory = $this->inventory();
        $this->optional();
        $other = $this->inventory(1, false);
        $other['purchase']->update(['productName' => $inventory['purchase']->productName]);
        $other['stock']->update(['productId' => $inventory['purchase']->productName]);
        $this->login();
        $this->postJson(route('saveSale'), $this->payload($other))->assertUnprocessable();
        $this->assertDatabaseCount('sale_products', 0);
        $this->assertSame(3, (int)$other['stock']->fresh()->currentStock);
    }

    public function test_edit_cannot_reassign_a_serial_sold_to_another_sale(): void
    {
        $inventory = $this->inventory();
        $this->login();
        [$sale, $item] = $this->existingSale($inventory);
        $this->optional();
        $inventory['serials'][2]->update(['saleId' => 999, 'status' => 'sold']);
        $this->postJson(route('sale.items.update', $sale->id), ['items' => [$item->id => ['qty' => 2]],
            'serialIdByItem' => [$item->id => [$inventory['serials'][0]->id, $inventory['serials'][2]->id]]])->assertUnprocessable();
        $this->assertSame(2, ProductSerial::where('saleId', $sale->id)->count());
        $this->assertSame(999, (int)$inventory['serials'][2]->fresh()->saleId);
        $this->assertSame(1, (int)$inventory['stock']->fresh()->currentStock);
    }

    public function test_edit_insufficient_stock_preserves_previous_serial_allocation(): void
    {
        $inventory = $this->inventory();
        $this->login();
        [$sale, $item] = $this->existingSale($inventory);
        $this->optional();
        $this->post(route('sale.items.update', $sale->id), ['items' => [$item->id => ['qty' => 4]]])->assertRedirect();
        $this->assertSame(2, ProductSerial::where('saleId', $sale->id)->count());
        $this->assertEquals(2, $item->fresh()->qty);
        $this->assertSame(1, (int)$inventory['stock']->fresh()->currentStock);
    }

    public function test_edit_cannot_add_cross_business_purchase(): void
    {
        $inventory = $this->inventory();
        $other = $this->inventory(2);
        $this->actingAs($this->actor('superadmin'), 'admin');
        [$sale, $item] = $this->existingSale($inventory);
        $this->postJson(route('sale.items.update', $sale->id), ['add' => ['purchaseId' => $other['purchase']->id,
            'qty' => 1, 'salePrice' => 150]])->assertUnprocessable();
        $this->assertDatabaseCount('invoice_items', 1);
        $this->assertSame(3, (int)$other['stock']->fresh()->currentStock);
    }

    public function test_serial_number_resolution_is_scoped_to_the_selected_purchase(): void
    {
        $inventory = $this->inventory();
        $other = $this->inventory(2);
        $other['serials'][0]->update(['serialNumber' => $inventory['serials'][0]->serialNumber]);
        $this->login();
        $payload = $this->payload($inventory, 1);
        unset($payload['serialId']);
        $payload['serialNumber'] = [[$inventory['serials'][0]->serialNumber]];
        $this->post(route('saveSale'), $payload)->assertRedirect();
        $this->assertNotNull($inventory['serials'][0]->fresh()->saleId);
        $this->assertNull($other['serials'][0]->fresh()->saleId);
    }

    public function test_edit_uses_current_policy_after_setting_changes(): void
    {
        $inventory = $this->inventory();
        $this->login();
        [$sale, $item] = $this->existingSale($inventory);
        $this->optional();
        $payload = ['items' => [$item->id => ['qty' => 3]]];
        BusinessSetup::find(1)->update(['sales_serial_selection_required' => true]);
        $this->postJson(route('sale.items.update', $sale->id), $payload)->assertUnprocessable();
        $this->assertEquals(2, $item->fresh()->qty);
        $this->assertSame(1, (int)$inventory['stock']->fresh()->currentStock);
        $this->optional();
        $this->post(route('sale.items.update', $sale->id), $payload)->assertRedirect();
        $this->assertEquals(3, $item->fresh()->qty);
        $this->assertSame(3, ProductSerial::where('saleId', $sale->id)->count());
    }

    public function test_sales_settings_preserve_csrf_protection(): void
    {
        $this->inventory();
        $this->actingAs($this->actor('superadmin'), 'admin');
        // Enable the real CSRF middleware check while retaining the isolated SQLite connection.
        $this->app['env'] = 'local';
        try {
            $payload = ['businessId' => 1, 'sales_serial_selection_required' => false];
            $this->postJson(route('sales.settings.update'), $payload)->assertStatus(419);
            $this->assertTrue(BusinessSetup::find(1)->sales_serial_selection_required);
            $this->withSession(['_token' => 'isolated-csrf-token'])->post(route('sales.settings.update'),
                $payload + ['_token' => 'isolated-csrf-token'])->assertRedirect();
            $this->assertFalse(BusinessSetup::find(1)->sales_serial_selection_required);
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_create_and_edit_views_render_with_serial_controls_in_both_policy_modes(): void
    {
        $inventory = $this->inventory();
        $this->login();
        [$sale, $item] = $this->existingSale($inventory);
        foreach ([true, false] as $required) {
            BusinessSetup::find(1)->update(['sales_serial_selection_required' => $required]);
            $this->get(route('newsale'))->assertOk()->assertSee('saleSerialModal')->assertSee('rn-sale-mobile-table');
            $this->get(route('sale.items.edit', $sale->id))->assertOk()->assertSee('Manage Serials')->assertSee('serialIdByItem');
        }
    }

    public function test_automatic_serial_assignments_are_visible_on_invoice_and_edit_view(): void
    {
        $inventory = $this->inventory();
        $this->optional();
        $this->login();
        $payload = $this->payload($inventory);
        unset($payload['serialId']);
        $this->post(route('saveSale'), $payload)->assertRedirect();
        $sale = SaleProduct::firstOrFail();
        $edit = $this->get(route('sale.items.edit', $sale->id))->assertOk();
        foreach (['a4', 'thermal80', 'thermal58'] as $printer) {
            $invoice = $this->get(route('invoiceGenerate', ['id' => $sale->id, 'printer' => $printer]))->assertOk()->assertSee('S/N:');
            if ($printer !== 'a4') { $invoice->assertSee('class="thermal-item-serial">S/N:', false); }
            foreach (array_slice($inventory['serials'], 0, 2) as $serial) {
                $invoice->assertSee($serial->serialNumber);
                $edit->assertSee($serial->serialNumber);
                $this->assertEquals($sale->id, $serial->fresh()->saleId);
                $this->assertNotNull($serial->fresh()->sold_at);
            }
            // Optional, isolated fixture export for browser layout checks; contains test data only.
            if (getenv('RN_EXPORT_INVOICE_FIXTURES') === '1') {
                file_put_contents(storage_path('framework/serial-invoice-fixture-'.$printer.'.html'), $invoice->getContent());
            }
        }
    }

    public function test_bangla_sales_validation_and_all_invoice_profiles_preserve_serials(): void
    {
        $inventory = $this->inventory();
        $user = $this->actor();
        $user->preferred_locale = 'bn';
        $user->save();
        $this->actingAs($user, 'admin');
        $payload = $this->payload($inventory);
        unset($payload['serialId']);
        $invalid = $this->postJson(route('saveSale'), $payload)->assertUnprocessable()->assertJsonValidationErrors('serialId.0');
        $this->assertSame(__('sales.select_exact', ['qty'=>2]), $invalid->json('errors')['serialId.0'][0]);
        $this->assertRolledBack($inventory);
        $this->optional();
        $this->post(route('saveSale'), $payload)->assertRedirect();
        $sale = SaleProduct::firstOrFail();
        foreach (['a4','thermal80','thermal58'] as $printer) {
            $response = $this->get(route('invoiceGenerate', ['id'=>$sale->id, 'printer'=>$printer]))->assertOk()
                ->assertSee('lang="bn"',false)->assertSee('সিরিয়াল')->assertSee('SER-1-0')->assertSee('SER-1-1');
            if (getenv('RN_EXPORT_INVOICE_FIXTURES') === '1') {
                file_put_contents(storage_path('framework/localized-invoice-bn-'.$printer.'.html'),$response->getContent());
            }
        }
        $create=$this->get(route('newsale'))->assertOk()->assertSee('সিরিয়াল');
        $edit=$this->get(route('sale.items.edit',$sale->id))->assertOk()->assertSee('সিরিয়াল');
        if(getenv('RN_EXPORT_INVOICE_FIXTURES')==='1') {
            file_put_contents(storage_path('framework/localized-sales-create-bn.html'),$create->getContent());
            file_put_contents(storage_path('framework/localized-sales-edit-bn.html'),$edit->getContent());
        }
        $this->assertSame(1,(int)$inventory['stock']->fresh()->currentStock);
        $this->assertEquals(295,$sale->grandTotal);
    }

    public function test_ambiguous_legacy_serialized_lines_are_rejected_without_mutation(): void
    {
        $inventory = $this->inventory();
        $this->login();
        [$sale, $item] = $this->existingSale($inventory);
        $copy = $item->replicate();
        $copy->save();
        $this->postJson(route('sale.items.update', $sale->id), ['items' => [$item->id => ['qty' => 0]]])
            ->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('invoice_items', 2);
        $this->assertEquals(2, $item->fresh()->qty);
        $this->assertSame(2, ProductSerial::where('saleId', $sale->id)->count());
        $this->assertSame(1, (int)$inventory['stock']->fresh()->currentStock);
    }

    public function test_edit_does_not_release_an_inconsistent_reserved_assignment_as_available(): void
    {
        $inventory = $this->inventory();
        $this->login();
        [$sale, $item] = $this->existingSale($inventory);
        $this->optional();
        $inventory['serials'][0]->update(['status' => 'reserved']);
        $this->postJson(route('sale.items.update', $sale->id), ['items' => [$item->id => ['qty' => 0]]])->assertUnprocessable();
        $this->assertSame('reserved', $inventory['serials'][0]->fresh()->status);
        $this->assertEquals($sale->id, $inventory['serials'][0]->fresh()->saleId);
        $this->assertSame(1, (int)$inventory['stock']->fresh()->currentStock);
        $this->assertEquals(2, $item->fresh()->qty);
    }

    public function test_first_salesmanager_cannot_read_another_business_invoice_or_serial_edit_view(): void
    {
        $inventory = $this->inventory(2);
        $actor = $this->actor('salesmanager', 1);
        $this->actingAs($actor, 'admin');
        $sale = SaleProduct::forceCreate(['businessId' => 2, 'salespersonId' => $actor->id]);
        $this->assertDenied($this->getJson(route('sale.items.edit', $sale->id)));
        $this->assertDenied($this->getJson(route('invoiceGenerate', $sale->id)));
        $this->getJson(route('ajax.purchase.serials.public', $inventory['purchase']->id))->assertNotFound();
        $this->assertSame(3, (int)$inventory['stock']->fresh()->currentStock);
    }

    public function test_edit_serial_validation_error_uses_item_id_and_is_visible_without_expanding(): void
    {
        $inventory = $this->inventory();
        $this->login();
        [$sale, $item] = $this->existingSale($inventory);
        $this->from(route('sale.items.edit', $sale->id))->post(route('sale.items.update', $sale->id),
            ['items' => [$item->id => ['qty' => 3]]])->assertSessionHasErrors('serialIdByItem.'.$item->id);
        $response = $this->get(route('sale.items.edit', $sale->id))->assertOk();
        $response->assertSee('data-role="backend-serial-error"', false);
        $response->assertSee('Select exactly 3 serial(s) for this item.');
        $this->assertEquals(2, $item->fresh()->qty);
    }
}
