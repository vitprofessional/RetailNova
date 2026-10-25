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

    protected function prepareForValidation(): void
    {
        $this->merge(['mail'=>filled($this->mail) ? strtolower(trim((string)$this->mail)) : null]);
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
            'image'         => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'remove_image'  => ['nullable','boolean'],
        ];
    }
}
