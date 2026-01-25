<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class PurchaseSaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // support update via purchaseId[] (array) or single purchaseId
            'purchaseId'          => ['nullable','array'],
            'purchaseId.*'        => ['nullable','integer','exists:purchase_products,id'],
            // Allow either a single product id or an array of product ids for multi-row purchases
            'productName'         => ['required'],
            'productName.*'       => ['nullable','integer','exists:products,id'],
            'supplierName'        => ['required','integer','exists:suppliers,id'],
            'purchaseDate'        => ['required','date'],
            // quantities may be a single integer or an array matching productName
            'quantity'            => ['required'],
            'quantity.*'          => ['nullable','integer','min:1'],
            'buyPrice'            => ['nullable'],
            'buyPrice.*'          => ['nullable','numeric','min:0'],
            'salePriceExVat'      => ['nullable'],
            'salePriceExVat.*'    => ['nullable','numeric','min:0'],
            'salePriceInVat'      => ['nullable'],
            'salePriceInVat.*'    => ['nullable','numeric','min:0'],
            'profitMargin'        => ['nullable'],
            'profitMargin.*'      => ['nullable','numeric'],
            'totalAmount'         => ['nullable'],
            'totalAmount.*'       => ['nullable','numeric','min:0'],
            'grandTotal'          => ['nullable','numeric','min:0'],
            'paidAmount'          => ['nullable','numeric','min:0'],
            'dueAmount'           => ['nullable','numeric','min:0'],
            'discountStatus'      => ['nullable','string'],
            'discountAmount'      => ['nullable','numeric','min:0'],
            'discountPercent'     => ['nullable','numeric','min:0'],
            'specialNote'         => ['nullable','string','max:1000'],
            'invoiceData'         => ['nullable','string','max:190'],
            'refData'             => ['nullable','string','max:190'],
            // Accept either an array of strings, or an array of arrays (per-row serial lists).
            // We normalize nested arrays into comma-separated strings in prepareForValidation().
            'serialNumber'        => ['nullable','array'],
            // No length cap on comma-separated serial lists
            'serialNumber.*'      => ['nullable','string'],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * Normalize `serialNumber` entries: if an element is an array, join items with comma.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('serialNumber')) {
            $serials = $this->input('serialNumber');
            if (is_array($serials)) {
                $normalized = array_map(function ($item) {
                    if (is_array($item)) {
                        // convert nested arrays into comma-separated string
                        return implode(',', array_map('strval', $item));
                    }
                    return $item;
                }, $serials);

                $this->merge(['serialNumber' => $normalized]);
            }
        }
    }

    /**
     * Log validation failures to help debugging in tests.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        try {
            \Log::error('PurchaseSaveRequest failed validation', [
                'input' => $this->all(),
                'errors' => $validator->errors()->toArray(),
            ]);
        } catch (\Exception $e) {
            // ignore logging failures
        }
        // removed file-based debug write; rely on framework logging above

        throw new ValidationException($validator);
    }
}
