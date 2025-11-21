<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseSaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchaseId'          => ['nullable','integer','exists:purchase_products,id'],
            'productName'         => ['required','integer','exists:products,id'],
            'supplierName'        => ['required','integer','exists:suppliers,id'],
            'purchaseDate'        => ['required','date'],
            'quantity'            => ['required','integer','min:1'],
            'buyPrice'            => ['nullable','numeric','min:0'],
            'salePriceExVat'      => ['nullable','numeric','min:0'],
            'salePriceInVat'      => ['nullable','numeric','min:0'],
            'profitMargin'        => ['nullable','numeric'],
            'totalAmount'         => ['nullable','numeric','min:0'],
            'grandTotal'          => ['nullable','numeric','min:0'],
            'paidAmount'          => ['nullable','numeric','min:0'],
            'dueAmount'           => ['nullable','numeric','min:0'],
            'discountStatus'      => ['nullable','string'],
            'discountAmount'      => ['nullable','numeric','min:0'],
            'discountPercent'     => ['nullable','numeric','min:0'],
            'specialNote'         => ['nullable','string','max:1000'],
            'invoiceData'         => ['nullable','string','max:190'],
            'refData'             => ['nullable','string','max:190'],
            'serialNumber'        => ['nullable','array'],
            'serialNumber.*'      => ['nullable','string','max:255'],
        ];
    }
}
