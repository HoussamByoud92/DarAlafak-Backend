<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Http\Resources\BookResource;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Services\CloudinaryService;
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
        // Exclude file fields from validated data to prevent temp paths being saved
        $data = collect($request->validated())->except(['front_image', 'back_image'])->toArray();

        $book = Book::create($data);

        // Handle relationships
        if ($request->has('author_ids')) {
            $book->authors()->sync($request->author_ids);
        }

        if ($request->has('keyword_ids')) {
            $book->keywords()->sync($request->keyword_ids);
        }

        // Handle file uploads to Cloudinary
        try {
            if ($request->hasFile('front_image')) {
                \Log::info('Uploading front_image to Cloudinary');
                $result = CloudinaryService::upload($request->file('front_image'), 'books');
                $book->front_image = $result['url'];
                \Log::info('Front image uploaded: ' . $result['url']);
            } else {
                \Log::info('No front_image file in request');
            }

            if ($request->hasFile('back_image')) {
                \Log::info('Uploading back_image to Cloudinary');
                $result = CloudinaryService::upload($request->file('back_image'), 'books');
                $book->back_image = $result['url'];
                \Log::info('Back image uploaded: ' . $result['url']);
            }
        } catch (\Exception $e) {
            \Log::error('Cloudinary upload error: ' . $e->getMessage());
            // Continue without failing - images will be null
        }

        // Save the image paths if any were uploaded
        if ($book->front_image || $book->back_image) {
            $book->save();
        }

        return new BookResource($book->load(['category', 'authors', 'publisher']));
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        \Log::info('BookController@update called for book ID: ' . $book->id);

        // Exclude file fields from validated data to prevent temp paths being saved
        $data = collect($request->validated())->except(['front_image', 'back_image'])->toArray();

        $book->update($data);

        // Handle relationships
        if ($request->has('author_ids')) {
            $book->authors()->sync($request->author_ids);
        }

        if ($request->has('keyword_ids')) {
            $book->keywords()->sync($request->keyword_ids);
        }

        // Handle file uploads to Cloudinary
        try {
            if ($request->hasFile('front_image')) {
                \Log::info('UPDATE: Uploading front_image to Cloudinary');
                // Delete old image from Cloudinary if exists
                if ($book->front_image) {
                    $publicId = CloudinaryService::getPublicIdFromUrl($book->front_image);
                    if ($publicId) {
                        CloudinaryService::delete($publicId);
                    }
                }
                $result = CloudinaryService::upload($request->file('front_image'), 'books');
                $book->front_image = $result['url'];
                \Log::info('UPDATE: Front image uploaded: ' . $result['url']);
            } else {
                \Log::info('UPDATE: No front_image file in request. All files: ' . json_encode($request->allFiles()));
            }

            if ($request->hasFile('back_image')) {
                \Log::info('UPDATE: Uploading back_image to Cloudinary');
                // Delete old image from Cloudinary if exists
                if ($book->back_image) {
                    $publicId = CloudinaryService::getPublicIdFromUrl($book->back_image);
                    if ($publicId) {
                        CloudinaryService::delete($publicId);
                    }
                }
                $result = CloudinaryService::upload($request->file('back_image'), 'books');
                $book->back_image = $result['url'];
                \Log::info('UPDATE: Back image uploaded: ' . $result['url']);
            }
        } catch (\Exception $e) {
            \Log::error('UPDATE: Cloudinary upload error: ' . $e->getMessage());
        }

        // Save the image paths if any were uploaded
        if ($book->front_image || $book->back_image) {
            $book->save();
        }

        return new BookResource($book->load(['category', 'authors', 'publisher']));
    }

    public function destroy(Book $book)
    {
        // Delete images from Cloudinary
        if ($book->front_image) {
            $publicId = CloudinaryService::getPublicIdFromUrl($book->front_image);
            if ($publicId) {
                CloudinaryService::delete($publicId);
            }
        }
        if ($book->back_image) {
            $publicId = CloudinaryService::getPublicIdFromUrl($book->back_image);
            if ($publicId) {
                CloudinaryService::delete($publicId);
            }
        }

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
