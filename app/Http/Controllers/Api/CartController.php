<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $cartItems = CartItem::where('user_id', $user->id)->with('book')->get();
        
        $total = $cartItems->sum(function ($item) {
            return $item->book->final_price * $item->quantity;
        });

        return response()->json([
            'data' => $cartItems,
            'total' => $total,
            'count' => $cartItems->sum('quantity')
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_id' => 'required|exists:books,id',
            'quantity' => 'required|integer|min:1',
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

        $existingCartItem = CartItem::where('user_id', $user->id)
            ->where('book_id', $request->book_id)
            ->first();

        if ($existingCartItem) {
            $existingCartItem->quantity += $request->quantity;
            $existingCartItem->save();
            $cartItem = $existingCartItem;
        } else {
            $cartItem = CartItem::create([
                'user_id' => $user->id,
                'book_id' => $request->book_id,
                'quantity' => $request->quantity,
            ]);
        }

        return response()->json([
            'data' => $cartItem->load('book'),
            'message' => 'Item added to cart successfully'
        ], 201);
    }

    public function update(Request $request, CartItem $item)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if user owns this cart item
        if ($item->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $item->quantity = $request->quantity;
        $item->save();

        return response()->json([
            'data' => $item->load('book'),
            'message' => 'Cart item updated successfully'
        ]);
    }

    public function destroy(Request $request, CartItem $item)
    {
        // Check if user owns this cart item
        if ($item->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $item->delete();

        return response()->json([
            'message' => 'Item removed from cart successfully'
        ]);
    }

    public function clear(Request $request)
    {
        $user = $request->user();
        CartItem::where('user_id', $user->id)->delete();

        return response()->json([
            'message' => 'Cart cleared successfully'
        ]);
    }
}
