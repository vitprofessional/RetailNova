<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductSaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization already handled separately; allow here.
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required','string','min:2','max:200'],
            'brand'    => ['required','integer','exists:brands,id'],
            'category' => ['required','integer','exists:categories,id'],
            'unitName' => ['required','integer','exists:product_units,id'],
            'quantity' => ['nullable','integer','min:0'],
            'details'  => ['nullable','string','max:1000'],
            'barCode'  => ['nullable','string','max:190'],
            'profileId'=> ['nullable','integer','exists:products,id']
        ];
    }
}
