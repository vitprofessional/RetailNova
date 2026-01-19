<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ignoreId = $this->input('profileId');
        return [
            'fullName'      => ['required','string','min:2','max:150'],
            'openingBalance'=> ['nullable','integer'],
            'mail'          => [
                'nullable','email','max:190',
                Rule::unique('customers','mail')
                    ->ignore($ignoreId)
                    ->where(fn($q) => $q->whereNull('deleted_at'))
            ],
            'mobile'        => [
                'nullable','string','min:6','max:25',
                Rule::unique('customers','mobile')
                    ->ignore($ignoreId)
                    ->where(fn($q) => $q->whereNull('deleted_at'))
            ],
            'country'       => ['nullable','string','max:100'],
            'state'         => ['nullable','string','max:100'],
            'city'          => ['nullable','string','max:100'],
            'area'          => ['nullable','string','max:150'],
        ];
    }
}
