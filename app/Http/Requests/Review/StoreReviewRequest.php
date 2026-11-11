<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'کد پیگیری نوبت الزامی است.',
            'rating.required' => 'انتخاب امتیاز الزامی است.',
            'rating.integer' => 'امتیاز نامعتبر است.',
            'rating.between' => 'امتیاز باید بین ۱ تا ۵ ستاره باشد.',
            'comment.max' => 'متن نظر حداکثر ۱۰۰۰ کاراکتر است.',
        ];
    }
}
