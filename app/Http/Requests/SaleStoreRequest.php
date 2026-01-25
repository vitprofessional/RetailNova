<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'            => ['required','date'],
            'customerId'      => ['required','integer','exists:customers,id'],
            'qty'             => ['required','array','min:1'],
            'qty.*'           => ['required','integer','min:1'],
            'purchaseData'    => ['required','array','min:1'],
            'purchaseData.*'  => ['required','integer','exists:purchase_products,id'],
            'salePrice'       => ['required','array','min:1'],
            'salePrice.*'     => ['required','numeric','min:0'],
            'buyPrice'        => ['required','array','min:1'],
            'buyPrice.*'      => ['required','numeric','min:0'],
            // Optional per-item warranty in days captured by UI as warranty_days[]
            'warranty_days'   => ['sometimes','array'],
            'warranty_days.*' => ['nullable','string'],
            'totalSaleAmount' => ['required','numeric','min:0'],
            'discountAmount'  => ['nullable','numeric','min:0'],
            'grandTotal'      => ['required','numeric','min:0'],
            'paidAmount'      => ['nullable','numeric','min:0'],
            'dueAmount'       => ['nullable','numeric','min:0'],
            'prevDue'         => ['nullable','numeric','min:0'],
            'curDue'          => ['nullable','numeric','min:0'],
            'reference'       => ['nullable','string','max:190'],
            'note'            => ['nullable','string','max:1000'],
            'serialId'        => ['sometimes','array'],
            'serialId.*'      => ['array'],
            'serialId.*.*'    => ['integer'],
            'serialNumber'    => ['sometimes','array'],
            'serialNumber.*'  => ['array'],
            'serialNumber.*.*'=> ['nullable','string'],
        ];
    }
}
