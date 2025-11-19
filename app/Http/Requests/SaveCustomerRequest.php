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
            'openingBalance'=> ['required','integer'],
            'mail'          => [
                'required','email','max:190',
                Rule::unique('customers','mail')
                    ->ignore($ignoreId)
                    ->where(fn($q) => $q->whereNull('deleted_at'))
            ],
            'mobile'        => [
                'required','string','min:6','max:25',
                Rule::unique('customers','mobile')
                    ->ignore($ignoreId)
                    ->where(fn($q) => $q->whereNull('deleted_at'))
            ],
            'country'       => ['required','string','max:100'],
            'state'         => ['required','string','max:100'],
            'city'          => ['required','string','max:100'],
            'area'          => ['required','string','max:150'],
        ];
    }
}
