<?php

namespace Tests\Feature;

use Tests\TestCase;

class MobileDetailLayoutTest extends TestCase
{
    public function test_transaction_details_use_labeled_mobile_line_cards(): void
    {
        foreach ([
            'resources/views/sale/viewSale.blade.php',
            'resources/views/sale/returnSaleDetails.blade.php',
            'resources/views/purchase/invoiceView.blade.php',
            'resources/views/quotation/view.blade.php',
            'resources/views/service/serviceInvoiceView.blade.php',
            'resources/views/invoice/invoicePage.blade.php',
        ] as $file) {
            $view = file_get_contents(base_path($file));
            $this->assertIsString($view, $file);
            $this->assertStringContainsString('rn-m-detail-lines', $view, $file);
            $this->assertStringContainsString('data-label=', $view, $file);
        }
    }

    public function test_mobile_detail_rules_are_screen_only_and_do_not_affect_print_output(): void
    {
        $css = file_get_contents(public_path('css/rn-m-components.css'));
        $invoice = file_get_contents(resource_path('views/invoice/invoicePage.blade.php'));

        $this->assertIsString($css);
        $this->assertStringContainsString('@media screen {', $css);
        $this->assertStringContainsString('.rn-m-detail-page table.rn-m-detail-lines', $css);
        $this->assertStringContainsString('@media print', $invoice);
        $this->assertStringNotContainsString('{{ $item->purchaseId }}', file_get_contents(resource_path('views/sale/returnSaleDetails.blade.php')));
    }
}
