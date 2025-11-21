<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductUnitSaveRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => ['required','string','min:1','max:100'],
            'profileId' => ['nullable','integer','exists:product_units,id']
        ];
    }
}
