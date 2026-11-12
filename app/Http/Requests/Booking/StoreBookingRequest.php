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
            'service_id.required' => __('validation.booking_service_required'),
            'service_id.exists' => __('validation.booking_service_exists'),
            'starts_at.required' => __('validation.booking_slot_required'),
            'starts_at.date' => __('validation.booking_slot_date'),
            'starts_at.after' => __('validation.booking_slot_future'),
            'customer_name.required' => __('validation.booking_name_required'),
            'customer_name.max' => __('validation.booking_name_max'),
            'customer_phone.required' => __('validation.booking_phone_required'),
            'customer_phone.regex' => __('validation.booking_phone_regex'),
            'notes.max' => __('validation.booking_notes_max'),
        ];
    }
}
