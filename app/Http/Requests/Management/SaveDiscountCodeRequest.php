<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveDiscountCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'code' => mb_strtoupper(trim((string) $this->input('code'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]{3,40}$/u'],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'integer', 'min:1', 'max:2000000000'],
            'min_amount' => ['nullable', 'integer', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => __('validation.discount_code_required'),
            'code.regex' => __('validation.discount_code_regex'),
            'code.unique' => __('discount.duplicate'),
            'type.required' => __('validation.discount_type_required'),
            'type.in' => __('validation.discount_type_in'),
            'value.required' => __('validation.discount_value_required'),
            'value.integer' => __('validation.discount_value_integer'),
            'value.min' => __('validation.discount_value_min'),
            'value.max' => __('validation.discount_value_max'),
            'expires_at.after_or_equal' => __('validation.discount_expires_after'),
        ];
    }

    /**
     * A percent code can never exceed 100.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('type') === 'percent' && (int) $this->input('value') > 100) {
                $validator->errors()->add('value', 'درصد تخفیف باید بین ۱ تا ۱۰۰ باشد.');
            }
        });
    }
}
