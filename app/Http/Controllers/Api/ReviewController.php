<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookReview;
use App\Models\Book;
use App\Http\Resources\BookReviewResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function index(Request $request, $bookId)
    {
        $book = Book::findOrFail($bookId);
        
        $query = BookReview::where('book_id', $bookId)->approved()->with('user');

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $reviews = $query->paginate($request->get('per_page', 10));

        return BookReviewResource::collection($reviews);
    }

    public function store(Request $request, $bookId)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:2000',
            'reviewer_name' => 'required|string|max:255',
            'reviewer_email' => 'nullable|email|max:254',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $book = Book::findOrFail($bookId);

        // Check if user already reviewed this book
        if (auth()->check()) {
            $existingReview = BookReview::where('book_id', $bookId)
                ->where('user_id', auth()->id())
                ->first();

            if ($existingReview) {
                return response()->json(['error' => 'You have already reviewed this book'], 400);
            }
        }

        // Check if user has purchased this book (for verified purchase)
        $isVerifiedPurchase = false;
        if (auth()->check()) {
            $isVerifiedPurchase = auth()->user()->orders()
                ->whereHas('items', function ($query) use ($bookId) {
                    $query->where('book_id', $bookId);
                })
                ->whereIn('status', ['delivered', 'shipped'])
                ->exists();
        }

        $review = BookReview::create([
            'book_id' => $bookId,
            'user_id' => auth()->id(),
            'reviewer_name' => $request->reviewer_name,
            'reviewer_email' => $request->reviewer_email,
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
            'is_verified_purchase' => $isVerifiedPurchase,
            'is_approved' => false, // Reviews need approval
        ]);

        // Update book rating and reviews count
        $this->updateBookRating($book);

        return response()->json([
            'message' => 'Review submitted successfully. It will be published after approval.',
            'data' => new BookReviewResource($review)
        ], 201);
    }

    public function update(Request $request, BookReview $review)
    {
        // Check if user owns this review
        if (!auth()->check() || $review->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $review->update([
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
            'is_approved' => false, // Reset approval status
        ]);

        // Update book rating
        $this->updateBookRating($review->book);

        return response()->json([
            'message' => 'Review updated successfully',
            'data' => new BookReviewResource($review)
        ]);
    }

    public function destroy(BookReview $review)
    {
        // Check if user owns this review or is admin
        if (auth()->check()) {
            $user = auth()->user();
            if ($review->user_id !== $user->id && !$user->is_staff && !$user->is_superuser) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $book = $review->book;
        $review->delete();

        // Update book rating
        $this->updateBookRating($book);

        return response()->json(['message' => 'Review deleted successfully']);
    }

    public function approve(BookReview $review)
    {
        // Check authorization
        if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $review->update(['is_approved' => true]);

        // Update book rating
        $this->updateBookRating($review->book);

        return response()->json([
            'message' => 'Review approved successfully',
            'data' => new BookReviewResource($review)
        ]);
    }

    public function unapprove(BookReview $review)
    {
        // Check authorization
        if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $review->update(['is_approved' => false]);

        // Update book rating
        $this->updateBookRating($review->book);

        return response()->json([
            'message' => 'Review unapproved successfully',
            'data' => new BookReviewResource($review)
        ]);
    }

    public function pending(Request $request)
    {
        // Check authorization
        if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $reviews = BookReview::where('is_approved', false)
            ->with(['book', 'user'])
            ->latest()
            ->paginate($request->get('per_page', 15));

        return BookReviewResource::collection($reviews);
    }

    private function updateBookRating(Book $book)
    {
        $approvedReviews = $book->reviews()->approved();
        $averageRating = $approvedReviews->avg('rating') ?: 0;
        $reviewsCount = $approvedReviews->count();

        $book->update([
            'rating' => round($averageRating, 2),
            'reviews_count' => $reviewsCount,
        ]);
    }
}
