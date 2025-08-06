<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\Book;
use App\Http\Resources\BookResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $wishlistItems = Wishlist::where('user_id', $user->id)
            ->with(['book.category', 'book.authors', 'book.publisher'])
            ->latest()
            ->paginate($request->get('per_page', 12));

        return response()->json([
            'data' => $wishlistItems->map(function ($item) {
                return new BookResource($item->book);
            }),
            'meta' => [
                'current_page' => $wishlistItems->currentPage(),
                'last_page' => $wishlistItems->lastPage(),
                'per_page' => $wishlistItems->perPage(),
                'total' => $wishlistItems->total(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_id' => 'required|exists:books,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $book = Book::findOrFail($request->book_id);

        // Check if book is available
        if (!$book->is_available || !$book->is_published) {
            return response()->json(['error' => 'Book is not available'], 400);
        }

        // Check if already in wishlist
        $existingWishlistItem = Wishlist::where('user_id', $user->id)
            ->where('book_id', $request->book_id)
            ->first();

        if ($existingWishlistItem) {
            return response()->json(['message' => 'Book already in wishlist'], 200);
        }

        $wishlistItem = Wishlist::create([
            'user_id' => $user->id,
            'book_id' => $request->book_id,
        ]);

        return response()->json([
            'message' => 'Book added to wishlist successfully',
            'data' => new BookResource($book)
        ], 201);
    }

    public function destroy(Request $request, $bookId)
    {
        $user = $request->user();
        
        $wishlistItem = Wishlist::where('user_id', $user->id)
            ->where('book_id', $bookId)
            ->first();

        if (!$wishlistItem) {
            return response()->json(['error' => 'Book not found in wishlist'], 404);
        }

        $wishlistItem->delete();

        return response()->json(['message' => 'Book removed from wishlist successfully']);
    }

    public function clear(Request $request)
    {
        $user = $request->user();
        Wishlist::where('user_id', $user->id)->delete();

        return response()->json(['message' => 'Wishlist cleared successfully']);
    }

    public function check(Request $request, $bookId)
    {
        $user = $request->user();
        
        $inWishlist = Wishlist::where('user_id', $user->id)
            ->where('book_id', $bookId)
            ->exists();

        return response()->json(['in_wishlist' => $inWishlist]);
    }
}
