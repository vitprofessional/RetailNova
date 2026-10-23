<?php

namespace App\Services;

use App\Models\SaleProduct;
use RuntimeException;

/** A sale that could not be cancelled; the message is ready to show to the user. */
class SaleCancellationException extends RuntimeException
{
    public function __construct(string $message, private readonly string $invoice)
    {
        parent::__construct($message);
    }

    public static function notFound(int $saleId): self
    {
        return new self(__('common.notification_sale_not_found'), '#'.$saleId);
    }

    public static function alreadyCancelled(SaleProduct $sale): self
    {
        return new self(__('sales.sale_already_cancelled', ['invoice' => $sale->invoice]), (string) $sale->invoice);
    }

    public static function stockNotRestored(SaleProduct $sale): self
    {
        return new self(__('sales.sale_cancel_stock_not_restored', ['invoice' => $sale->invoice]), (string) $sale->invoice);
    }

    /** The invoice number of the failed sale, or "#<id>" when the sale was not found. */
    public function invoice(): string
    {
        return $this->invoice;
    }
}
