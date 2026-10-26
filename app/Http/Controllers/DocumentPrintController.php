<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetup;
use App\Models\Customer;
use App\Models\InvoiceItem;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseProduct;
use App\Models\PurchaseReturn;
use App\Models\Quotation;
use App\Models\SaleProduct;
use App\Models\SaleReturn;
use App\Models\ServiceInvoice;
use App\Models\Supplier;
use App\Support\LocalizedNumber;
use App\Services\PurchaseWorkflow;
use Illuminate\Http\Request;

class DocumentPrintController extends Controller
{
    public function print(Request $request, string $type, int $id, PurchaseWorkflow $workflow)
    {
        abort_unless(in_array($type, [
            'sale-invoice', 'purchase-invoice', 'quotation', 'service-invoice',
            'sale-return', 'purchase-return', 'customer-ledger', 'supplier-ledger',
        ], true), 404);

        [$receipt, $business] = match ($type) {
            'sale-invoice' => $this->saleInvoice($id),
            'purchase-invoice' => $this->purchaseInvoice($id, $workflow),
            'quotation' => $this->quotation($id),
            'service-invoice' => $this->serviceInvoice($id),
            'sale-return' => $this->saleReturn($id),
            'purchase-return' => $this->purchaseReturn($id),
            'customer-ledger' => $this->contactLedger('customer', $id),
            'supplier-ledger' => $this->contactLedger('supplier', $id),
        };

        $paper = $request->query('size', $request->query('printer', $business?->default_invoice_printer ?: 'thermal80'));
        $paper = in_array($paper, ['a4', 'thermal80', 'thermal58'], true) ? $paper : 'thermal80';
        $templateType = match ($type) {
            'sale-return', 'purchase-return' => 'return',
            'customer-ledger', 'supplier-ledger' => 'ledger',
            default => $type,
        };

        return view('documents.print', compact('receipt', 'business', 'type', 'templateType', 'paper'));
    }

    private function saleInvoice(int $id): array
    {
        $sale = SaleProduct::with(['business', 'customer', 'items.purchase.product.unitModel'])->findOrFail($id);
        $actor = auth('admin')->user();
        abort_unless($actor, 403);
        if (!in_array(strtolower((string) $actor->role), ['admin', 'superadmin'], true)
            && (int) $sale->salespersonId !== (int) $actor->id) {
            abort(403);
        }

        $items = $sale->items->map(fn (InvoiceItem $line) => [
            'title' => $line->purchase?->product?->name ?? __('invoices.item').' #'.$line->purchaseId,
            'detail' => LocalizedNumber::count($line->qty).' × '.LocalizedNumber::money($line->salePrice),
            'amount' => LocalizedNumber::money($line->totalSale ?? ((float) $line->qty * (float) $line->salePrice)),
        ])->all();
        $total = (float) ($sale->grandTotal ?? $sale->totalSale ?? 0);
        $paid = (float) ($sale->paidAmount ?? 0);
        $due = (float) ($sale->curDue ?? $sale->invoiceDue ?? max(0, $total - $paid));
        $receipt = $this->receipt(
            __('common.status').' · '.__($due <= 0 ? 'purchases.paid' : ($paid > 0 ? 'purchases.partial' : 'purchases.due')),
            $total, __('common.invoice'), (string) $sale->invoice,
            __('invoices.bill_to'), Customer::displayLabel($sale->customer, $sale->customerId, $sale->businessId),
            $sale->customer?->mobile, $items,
            [
                ['label' => __('invoices.subtotal'), 'amount' => LocalizedNumber::money((float) $sale->items->sum(fn ($line) => (float) ($line->totalSale ?? ((float) $line->qty * (float) $line->salePrice))))],
                ...(float) ($sale->discountAmount ?? 0) > 0 ? [['label' => __('common.discount'), 'amount' => LocalizedNumber::money($sale->discountAmount)]] : [],
                ['label' => __('common.grand_total'), 'amount' => LocalizedNumber::money($total), 'is_total' => true],
                ['label' => __('invoices.paid'), 'amount' => LocalizedNumber::money($paid)],
                ['label' => __('invoices.due'), 'amount' => LocalizedNumber::money($due)],
            ],
        );
        if ($method = $sale->paymentMethodLabel()) {
            array_splice($receipt['totals'], 3, 0, [['label' => __('sales.payment_method'), 'amount' => $method]]);
        }

        return [$receipt, $sale->business];
    }

    private function purchaseInvoice(int $id, PurchaseWorkflow $workflow): array
    {
        $invoice = $workflow->header($id)->load(['supplier', 'items.product']);
        $items = $invoice->items->map(fn (PurchaseProduct $line) => [
            'title' => $line->product?->name ?? __('common.product'),
            'detail' => LocalizedNumber::count($line->qty).' × '.LocalizedNumber::money($line->buyPrice),
            'amount' => LocalizedNumber::money($line->totalAmount ?? ((float) $line->qty * (float) $line->buyPrice)),
        ])->all();
        $business = BusinessSetup::query()->find($invoice->businessId);
        return [$this->receipt(
            __('purchases.payment_status').' · '.__(((float) $invoice->due_amount <= 0) ? 'purchases.paid' : (((float) $invoice->paid_amount > 0) ? 'purchases.partial' : 'purchases.due')),
            $invoice->grand_total, __('purchases.invoice'), (string) $invoice->invoice,
            __('purchases.supplier'), $invoice->supplier?->name ?: __('mobile.suppliers'), $invoice->supplier?->mobile,
            $items,
            [
                ['label' => __('purchases.subtotal'), 'amount' => LocalizedNumber::money($invoice->total_amount)],
                ...(float) $invoice->discount_amount > 0 ? [['label' => __('common.discount'), 'amount' => LocalizedNumber::money($invoice->discount_amount)]] : [],
                ['label' => __('common.grand_total'), 'amount' => LocalizedNumber::money($invoice->grand_total), 'is_total' => true],
                ['label' => __('invoices.paid'), 'amount' => LocalizedNumber::money($invoice->paid_amount)],
                ['label' => __('invoices.due'), 'amount' => LocalizedNumber::money($invoice->due_amount)],
            ],
        ), $business];
    }

    private function quotation(int $id): array
    {
        $quote = Quotation::with(['customer', 'items.product'])->findOrFail($id);
        $items = $quote->items->map(fn ($line) => [
            'title' => $line->product?->name ?: ($line->description ?: __('common.product')),
            'detail' => LocalizedNumber::count($line->qty).' × '.LocalizedNumber::money($line->unit_price),
            'amount' => LocalizedNumber::money($line->line_total),
        ])->all();
        $business = BusinessSetup::query()->find($quote->businessId);
        return [$this->receipt(
            __('common.status').' · '.(\Illuminate\Support\Facades\Lang::has('common.'.$quote->status) ? __('common.'.$quote->status) : $quote->status),
            $quote->grand_total, __('common.quotation'), (string) $quote->quote_number,
            __('common.bill_to'), Customer::displayLabel($quote->customer, $quote->customer_id, $quote->businessId), $quote->customer?->mobile,
            $items,
            [
                ['label' => __('invoices.subtotal'), 'amount' => LocalizedNumber::money($quote->subtotal)],
                ...(float) $quote->discount_total > 0 ? [['label' => __('common.discount'), 'amount' => LocalizedNumber::money($quote->discount_total)]] : [],
                ['label' => __('common.grand_total'), 'amount' => LocalizedNumber::money($quote->grand_total), 'is_total' => true],
            ],
        ), $business];
    }

    private function serviceInvoice(int $id): array
    {
        $invoice = ServiceInvoice::with('items')->findOrFail($id);
        $customer = $invoice->customer_id ? Customer::query()->find($invoice->customer_id) : null;
        $business = BusinessSetup::query()->find($invoice->businessId);
        $items = $invoice->items->map(fn ($line) => [
            'title' => $line->service_name,
            'detail' => LocalizedNumber::count($line->qty).' × '.LocalizedNumber::money($line->rate),
            'amount' => LocalizedNumber::money($line->line_total),
        ])->all();
        return [$this->receipt(
            __('common.status').' · '.__('common.service_invoice'), $invoice->total_amount,
            __('common.service_invoice'), (string) $invoice->invoice_number,
            __('invoices.bill_to'), Customer::displayLabel($customer, $invoice->customer_id, $invoice->businessId), $customer?->mobile,
            $items, [['label' => __('invoices.total'), 'amount' => LocalizedNumber::money($invoice->total_amount), 'is_total' => true]],
        ), $business];
    }

    private function saleReturn(int $id): array
    {
        $return = SaleReturn::with(['sale.business', 'sale.customer', 'items.product'])->findOrFail($id);
        $sale = $return->sale;
        $business = $sale?->business;
        $items = $return->items->map(fn ($line) => [
            'title' => $line->product?->name ?: __('common.product'),
            'detail' => LocalizedNumber::count($line->qty),
            'amount' => '',
        ])->all();
        return [$this->receipt(__('common.status').' · '.__('common.return'), $return->totalReturnAmount,
            __('common.return'), (string) ($sale?->invoice ?: $return->id), __('invoices.bill_to'),
            Customer::displayLabel($sale?->customer, $sale?->customerId, $return->businessId), $sale?->customer?->mobile,
            $items, [['label' => __('common.total'), 'amount' => LocalizedNumber::money($return->totalReturnAmount), 'is_total' => true]],
        ), $business];
    }

    private function purchaseReturn(int $id): array
    {
        $return = PurchaseReturn::with(['purchase.invoiceHeader', 'purchase.supplier', 'returnItems.product'])->findOrFail($id);
        $purchase = $return->purchase;
        $business = BusinessSetup::query()->find($return->businessId);
        $supplier = $purchase ? Supplier::query()->find($purchase->supplier) : null;
        $items = $return->returnItems->map(fn ($line) => [
            'title' => $line->product?->name ?: __('common.product'),
            'detail' => LocalizedNumber::count($line->qty),
            'amount' => '',
        ])->all();
        return [$this->receipt(__('common.status').' · '.__('common.return'), $return->totalReturnAmount,
            __('common.return'), (string) ($purchase?->invoice ?: $return->id), __('purchases.supplier'),
            $supplier?->name ?: __('mobile.suppliers'), $supplier?->mobile,
            $items, [['label' => __('common.total'), 'amount' => LocalizedNumber::money($return->totalReturnAmount), 'is_total' => true]],
        ), $business];
    }

    private function contactLedger(string $type, int $id): array
    {
        $actor = auth('admin')->user();
        abort_unless($actor, 403);
        $contact = ($type === 'customer' ? Customer::query() : Supplier::query())->findOrFail($id);
        $business = BusinessSetup::query()->findOrFail($contact->businessId);
        $rows = [];
        if ($type === 'customer') {
            foreach (SaleProduct::query()->where('customerId', $contact->id)->orderBy('date')->get() as $sale) {
                $rows[] = ['title' => $sale->invoice ?: __('common.invoice').' #'.$sale->id,
                    'detail' => LocalizedNumber::money($sale->paidAmount ?? 0).' · '.__('mobile.ledger_paid'),
                    'amount' => LocalizedNumber::money($sale->grandTotal ?? $sale->totalSale ?? 0),
                    'raw_amount' => (float) ($sale->grandTotal ?? $sale->totalSale ?? 0)];
            }
        } else {
            foreach (PurchaseInvoice::query()->where('supplier_id', $contact->id)->orderBy('purchase_date')->get() as $invoice) {
                $rows[] = ['title' => $invoice->invoice ?: __('purchases.invoice').' #'.$invoice->id,
                    'detail' => LocalizedNumber::money($invoice->paid_amount).' · '.__('mobile.ledger_paid'),
                    'amount' => LocalizedNumber::money($invoice->grand_total), 'raw_amount' => (float) $invoice->grand_total];
            }
        }
        abort_unless((int) $actor->businessId === (int) $contact->businessId || $actor->hasSuperAdminPrivileges(), 404);
        $total = array_sum(array_column($rows, 'raw_amount'));
        return [$this->receipt(__('mobile.ledger_transactions'), $total, __('mobile.ledger_'.$type), (string) $contact->name,
            __('mobile.ledger_'.$type), $contact->name, $contact->mobile, $rows,
            [['label' => __('mobile.ledger_total_'.$type), 'amount' => LocalizedNumber::money($total), 'is_total' => true]],
        ), $business];
    }

    private function receipt(string $status, mixed $amount, string $documentLabel, string $number, string $partyLabel,
        string $partyName, ?string $partyMeta, array $items, array $totals): array
    {
        return [
            'status' => $status,
            'amount' => LocalizedNumber::money($amount),
            'document_label' => $documentLabel,
            'document_number' => $number,
            'party_label' => $partyLabel,
            'party_name' => $partyName,
            'party_meta' => $partyMeta,
            'items' => $items,
            'totals' => $totals,
            'empty_label' => __('invoices.no_items_found'),
        ];
    }
}
