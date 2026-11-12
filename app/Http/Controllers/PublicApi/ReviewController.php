<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    /**
     * A customer rates a completed booking. Reviews land in the moderation
     * queue (is_approved=false) and appear publicly only after approval.
     */
    public function store(StoreReviewRequest $request, Business $business): RedirectResponse
    {
        abort_unless($business->is_active, 404);

        $appointment = Appointment::query()
            ->where('code', $request->string('code')->toString())
            ->where('business_id', $business->id)
            ->first();

        if ($appointment === null) {
            return back()->withErrors(['code' => __('review.code_not_found')]);
        }

        if ($appointment->status !== Appointment::STATUS_COMPLETED) {
            return back()->withErrors(['code' => __('review.not_completed')]);
        }

        if ($appointment->review()->exists()) {
            return back()->withErrors(['code' => __('review.duplicate')]);
        }

        Review::query()->create([
            'business_id' => $business->id,
            'appointment_id' => $appointment->id,
            'customer_name' => $appointment->customer_name,
            'rating' => $request->integer('rating'),
            'comment' => $request->string('comment')->toString() ?: null,
            'is_approved' => false,
        ]);

        return back()->with('success', __('reviews.submitted'));
    }
}
