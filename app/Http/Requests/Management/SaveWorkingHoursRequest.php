<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

class SaveWorkingHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'windows' => ['required', 'array', 'max:28'],
            'windows.*.weekday' => ['required', 'integer', 'between:0,6'],
            'windows.*.starts_at' => ['required', 'date_format:H:i'],
            'windows.*.ends_at' => [
                'required',
                'date_format:H:i',
                'different:windows.*.starts_at',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'windows.required' => __('validation.windows_required'),
            'windows.*.weekday.between' => 'روز هفته معتبر نیست.',
            'windows.*.starts_at.date_format' => 'فرمت ساعت شروع صحیح نیست (مثال: 09:00).',
            'windows.*.ends_at.date_format' => 'فرمت ساعت پایان صحیح نیست (مثال: 18:00).',
            'windows.*.ends_at.different' => 'ساعت پایان باید متفاوت از ساعت شروع باشد.',
        ];
    }
}
