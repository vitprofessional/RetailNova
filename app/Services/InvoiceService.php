<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\PurchaseProduct;

class InvoiceService
{
    public function generatePurchaseInvoice(): string
    {
        return $this->generateInvoice('purchase');
    }

    public function generateSaleInvoice(): string
    {
        return $this->generateInvoice('sale');
    }

    protected function generateInvoice(string $type): string
    {
        $today = date('Y-m-d');
        return DB::transaction(function () use ($type, $today) {
            // Lock existing rows for today of this type
            $row = DB::table('invoice_sequences')
                ->where(['type' => $type, 'seq_date' => $today])
                ->lockForUpdate()
                ->orderByDesc('seq')
                ->first();
            $nextSeq = $row ? ($row->seq + 1) : 1;
            $invoiceNumber = strtoupper(substr($type,0,3)) . date('Ymd') . str_pad($nextSeq, 6, '0', STR_PAD_LEFT);
            DB::table('invoice_sequences')->insert([
                'type' => $type,
                'seq_date' => $today,
                'seq' => $nextSeq,
                'invoice_number' => $invoiceNumber,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return $invoiceNumber;
        });
    }
}
