<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

class SaveStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'specialty' => ['nullable', 'string', 'max:120'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام همکار الزامی است.',
        ];
    }
}
