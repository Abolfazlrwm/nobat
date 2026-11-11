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
            'code.required' => 'کد تخفیف الزامی است.',
            'code.regex' => 'کد فقط می‌تواند حروف انگلیسی، عدد، خط تیره و زیرخط داشته باشد (حداقل ۳ کاراکتر).',
            'code.unique' => 'این کد قبلاً برای همین کسب‌وکار ثبت شده است.',
            'type.required' => 'نوع تخفیف الزامی است.',
            'type.in' => 'نوع تخفیف معتبر نیست.',
            'value.required' => 'مقدار تخفیف الزامی است.',
            'value.integer' => 'مقدار تخفیف باید عدد باشد.',
            'value.min' => 'مقدار تخفیف باید مثبت باشد.',
            'value.max' => 'اگر نوع «درصدی» است، مقدار باید حداکثر ۱۰۰ باشد.',
            'expires_at.after_or_equal' => 'تاریخ پایان باید بعد یا هم‌زمان با تاریخ شروع باشد.',
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
