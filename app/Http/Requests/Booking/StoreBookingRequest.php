<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'staff_id' => ['nullable', 'integer', 'exists:staff,id'],
            'starts_at' => ['required', 'date', 'after:now'],
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'notes' => ['nullable', 'string', 'max:500'],
            'discount_code' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.required' => 'انتخاب خدمت الزامی است.',
            'service_id.exists' => 'خدمت انتخاب‌شده معتبر نیست.',
            'starts_at.required' => 'انتخاب بازه زمانی الزامی است.',
            'starts_at.date' => 'بازه زمانی معتبر نیست.',
            'starts_at.after' => 'بازه زمانی انتخابی باید در آینده باشد.',
            'customer_name.required' => 'نام و نام خانوادگی الزامی است.',
            'customer_name.max' => 'نام حداکثر می‌تواند ۱۰۰ کاراکتر باشد.',
            'customer_phone.required' => 'شماره موبایل الزامی است.',
            'customer_phone.regex' => 'شماره موبایل باید با فرمت ۰۹xxxxxxxxx وارد شود.',
            'notes.max' => 'توضیحات حداکثر می‌تواند ۵۰۰ کاراکتر باشد.',
        ];
    }
}
