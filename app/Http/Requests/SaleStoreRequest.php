<?php

namespace App\Http\Requests;

use App\Models\SaleProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $lineFields = ['purchaseData', 'qty', 'salePrice', 'buyPrice'];
            $lineKeys = null;
            foreach ($lineFields as $field) {
                $values = $this->input($field);
                if (!is_array($values)) {
                    continue; // The field's primary rule reports the missing/invalid array.
                }
                $keys = array_keys($values);
                if ($lineKeys === null) {
                    $lineKeys = $keys;
                    continue;
                }
                if ($keys !== $lineKeys) {
                    $validator->errors()->add($field, __('sales.sale_lines_must_match'));
                }
            }
        });
    }

    public function rules(): array
    {
        return [
            'date'            => ['required','date'],
            'customerId'      => ['required','integer','exists:customers,id'],
            'qty'             => ['required','array','min:1'],
            'qty.*'           => ['required','integer','min:1','max:1000000'],
            'purchaseData'    => ['required','array','min:1'],
            'purchaseData.*'  => ['required','integer','exists:purchase_products,id'],
            'salePrice'       => ['required','array','min:1'],
            'salePrice.*'     => ['required','numeric','decimal:0,2','min:0','max:9999999999.99'],
            'buyPrice'        => ['required','array','min:1'],
            'buyPrice.*'      => ['required','numeric','decimal:0,2','min:0','max:9999999999.99'],
            // Optional per-item warranty in days captured by UI as warranty_days[]
            'warranty_days'   => ['sometimes','array'],
            'warranty_days.*' => ['nullable','string'],
            // Sale and invoice monetary columns use DECIMAL(12, 2): ten whole
            // digits plus two decimal places.
            'totalSaleAmount' => ['required','numeric','decimal:0,2','min:0','max:9999999999.99'],
            'discountAmount'  => ['nullable','numeric','decimal:0,2','min:0','max:9999999999.99'],
            'additionalChargeName'   => ['nullable','string','max:190'],
            'additionalChargeAmount' => ['nullable','numeric','decimal:0,2','min:0','max:9999999999.99'],
            'grandTotal'      => ['required','numeric','decimal:0,2','min:0','max:9999999999.99'],
            'paidAmount'      => ['nullable','numeric','decimal:0,2','min:0','max:9999999999.99'],
            'payment_method'  => ['nullable','string',Rule::in(SaleProduct::PAYMENT_METHODS)],
            'dueAmount'       => ['nullable','numeric','decimal:0,2','min:0','max:9999999999.99'],
            'prevDue'         => ['nullable','numeric','decimal:0,2','min:0','max:9999999999.99'],
            'curDue'          => ['nullable','numeric','decimal:0,2','min:0','max:9999999999.99'],
            'reference'       => ['nullable','string','max:190'],
            'note'            => ['nullable','string','max:1000'],
            'saleAction'      => ['nullable','in:save,save_print'],
            'printerProfile'  => ['nullable','in:a4,thermal80,thermal58'],
            'serialId'        => ['sometimes','array'],
            'serialId.*'      => ['array'],
            'serialId.*.*'    => ['integer'],
            'serialNumber'    => ['sometimes','array'],
            'serialNumber.*'  => ['array'],
            'serialNumber.*.*'=> ['nullable','string'],
        ];
    }
}
