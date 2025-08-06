<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Http\Resources\BookResource;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::with(['category', 'authors', 'publisher'])
            ->published()
            ->available();

        // Filters
        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('author')) {
            $query->whereHas('authors', function ($q) use ($request) {
                $q->where('authors.id', $request->author);
            });
        }

        if ($request->has('search')) {
            $query->where('title', 'ILIKE', '%' . $request->search . '%');
        }

        if ($request->has('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }

        if ($request->has('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $books = $query->paginate($request->get('per_page', 12));

        return BookResource::collection($books);
    }

    public function show($slug)
    {
        $book = Book::where('slug', $slug)
            ->with(['category', 'authors', 'publisher', 'serie', 'physicalFormat', 'keywords', 'reviews'])
            ->firstOrFail();
            
        $book->increment('views_count');
        
        return new BookResource($book);
    }

    public function store(StoreBookRequest $request)
    {
        $book = Book::create($request->validated());
        
        // Handle relationships
        if ($request->has('author_ids')) {
            $book->authors()->sync($request->author_ids);
        }
        
        if ($request->has('keyword_ids')) {
            $book->keywords()->sync($request->keyword_ids);
        }

        // Handle file uploads
        if ($request->hasFile('front_image')) {
            $book->addMediaFromRequest('front_image')
                ->toMediaCollection('front_cover');
        }

        if ($request->hasFile('back_image')) {
            $book->addMediaFromRequest('back_image')
                ->toMediaCollection('back_cover');
        }

        return new BookResource($book->load(['category', 'authors', 'publisher']));
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        $book->update($request->validated());
        
        // Handle relationships
        if ($request->has('author_ids')) {
            $book->authors()->sync($request->author_ids);
        }
        
        if ($request->has('keyword_ids')) {
            $book->keywords()->sync($request->keyword_ids);
        }

        // Handle file uploads
        if ($request->hasFile('front_image')) {
            $book->clearMediaCollection('front_cover');
            $book->addMediaFromRequest('front_image')
                ->toMediaCollection('front_cover');
        }

        if ($request->hasFile('back_image')) {
            $book->clearMediaCollection('back_cover');
            $book->addMediaFromRequest('back_image')
                ->toMediaCollection('back_cover');
        }

        return new BookResource($book->load(['category', 'authors', 'publisher']));
    }

    public function destroy(Book $book)
    {
        $book->delete();
        return response()->json(['message' => 'Book deleted successfully']);
    }

    public function featured()
    {
        $books = Book::with(['category', 'authors', 'publisher'])
            ->featured()
            ->published()
            ->available()
            ->limit(8)
            ->get();

        return BookResource::collection($books);
    }

    public function recent()
    {
        $books = Book::with(['category', 'authors', 'publisher'])
            ->published()
            ->available()
            ->latest()
            ->limit(6)
            ->get();

        return BookResource::collection($books);
    }
}
