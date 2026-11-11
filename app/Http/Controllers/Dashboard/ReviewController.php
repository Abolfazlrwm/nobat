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

        $reviews = Review::query()
            ->where('business_id', $business->id)
            ->with('appointment.service:id,name')
            ->orderByDesc('is_approved')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Review $review) => [
                'id' => $review->id,
                'customer_name' => $review->customer_name,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'is_approved' => $review->is_approved,
                'service' => $review->appointment?->service?->name,
                'created_at' => $review->created_at?->format('Y-m-d'),
            ]);

        $stats = [
            'pending' => Review::query()->where('business_id', $business->id)->where('is_approved', false)->count(),
            'approved' => Review::query()->where('business_id', $business->id)->where('is_approved', true)->count(),
            'average' => $reviews->where('is_approved', true)->avg('rating'),
        ];

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

        return back()->with('success', 'نظر تأیید شد و اکنون عمومی است.');
    }

    public function destroy(Request $request, Review $review): RedirectResponse
    {
        $this->authorizeAccess($request, $review);

        $review->delete();
        Cache::forget('nobat.directory');

        return back()->with('success', 'نظر حذف شد.');
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
