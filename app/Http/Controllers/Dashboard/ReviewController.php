<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\ResolvesManagedBusiness;
use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    use ResolvesManagedBusiness;

    public function index(Request $request): Response
    {
        $business = $this->managedBusiness($request);

        $base = Review::query()->where('business_id', $business->id);

        // Stats are global to the business, independent of the page window.
        $stats = [
            'pending' => (clone $base)->where('is_approved', false)->count(),
            'approved' => (clone $base)->where('is_approved', true)->count(),
            'average' => (clone $base)->where('is_approved', true)->avg('rating'),
        ];

        $reviews = (clone $base)
            ->with('appointment.service:id,name')
            ->orderByDesc('is_approved')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Review $review) => [
                'id' => $review->id,
                'customer_name' => $review->customer_name,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'is_approved' => $review->is_approved,
                'reply' => $review->reply,
                'replied_at' => $review->replied_at?->format('Y-m-d'),
                'service' => $review->appointment?->service?->name,
                'created_at' => $review->created_at?->format('Y-m-d'),
            ]);

        return Inertia::render('dashboard/Reviews', [
            'reviews' => $reviews,
            'stats' => [
                'pending' => $stats['pending'],
                'approved' => $stats['approved'],
                'average' => $stats['average'] ? round((float) $stats['average'], 1) : null,
            ],
        ]);
    }

    public function approve(Request $request, Review $review): RedirectResponse
    {
        $this->authorizeAccess($request, $review);

        $review->update(['is_approved' => true]);
        Cache::forget('nobat.directory');

        return back()->with('success', __('review.approved'));
    }

    public function reply(Request $request, Review $review): RedirectResponse
    {
        $this->authorizeAccess($request, $review);

        $validated = $request->validate([
            'reply' => ['required', 'string', 'max:1000'],
        ], [
            'reply.required' => __('review.reply_required'),
            'reply.max' => __('review.reply_max'),
        ]);

        $review->update([
            'reply' => $validated['reply'],
            'replied_at' => now(),
        ]);

        return back()->with('success', __('review.replied'));
    }

    public function destroy(Request $request, Review $review): RedirectResponse
    {
        $this->authorizeAccess($request, $review);

        $review->delete();
        Cache::forget('nobat.directory');

        return back()->with('success', __('review.deleted'));
    }

    /**
     * Managers moderate reviews of their own business; super-admins may
     * moderate any review.
     */
    private function authorizeAccess(Request $request, Review $review): void
    {
        if ($request->user()->isSuperAdmin()) {
            return;
        }

        abort_unless($review->business_id === $this->managedBusiness($request)->id, 404);
    }
}
