<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

class SaveServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'price' => ['required', 'integer', 'min:0', 'max:2000000000'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام خدمت الزامی است.',
            'duration_minutes.required' => 'مدت زمان خدمت الزامی است.',
            'duration_minutes.min' => 'حداقل مدت زمان ۵ دقیقه است.',
            'price.required' => 'قیمت خدمت الزامی است.',
            'price.min' => 'قیمت نمی‌تواند منفی باشد.',
        ];
    }
}
