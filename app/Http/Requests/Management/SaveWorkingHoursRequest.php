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
            'windows.*.weekday.between' => __('validation.weekday_invalid'),
            'windows.*.starts_at.date_format' => __('validation.working_hour_starts_format'),
            'windows.*.ends_at.date_format' => __('validation.working_hour_ends_format'),
            'windows.*.ends_at.different' => __('validation.working_hour_ends_different'),
        ];
    }
}
