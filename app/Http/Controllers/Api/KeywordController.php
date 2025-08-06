<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Keyword;
use App\Http\Resources\KeywordResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KeywordController extends Controller
{
    public function index(Request $request)
    {
        $query = Keyword::active();

        // Search functionality
        if ($request->has('search')) {
            $query->where('name', 'ILIKE', '%' . $request->search . '%');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $keywords = $query->paginate($request->get('per_page', 20));

        return KeywordResource::collection($keywords);
    }

    public function show($slug)
    {
        $keyword = Keyword::where('slug', $slug)
            ->with(['books' => function ($query) {
                $query->published()->available()->with(['category', 'authors', 'publisher']);
            }])
            ->firstOrFail();

        return new KeywordResource($keyword);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:keywords,name',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $keyword = Keyword::create($request->all());

        return new KeywordResource($keyword);
    }

    public function update(Request $request, Keyword $keyword)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:keywords,name,' . $keyword->id,
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $keyword->update($request->all());

        return new KeywordResource($keyword);
    }

    public function destroy(Keyword $keyword)
    {
        // Check if keyword has books
        if ($keyword->books()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete keyword with associated books'
            ], 400);
        }

        $keyword->delete();
        return response()->json(['message' => 'Keyword deleted successfully']);
    }

    public function books($slug, Request $request)
    {
        $keyword = Keyword::where('slug', $slug)->firstOrFail();
        
        $books = $keyword->books()
            ->published()
            ->available()
            ->with(['category', 'authors', 'publisher'])
            ->paginate($request->get('per_page', 12));

        return response()->json([
            'keyword' => new KeywordResource($keyword),
            'books' => $books
        ]);
    }

    public function popular(Request $request)
    {
        $keywords = Keyword::active()
            ->withCount('books')
            ->having('books_count', '>', 0)
            ->orderBy('books_count', 'desc')
            ->limit($request->get('limit', 10))
            ->get();

        return KeywordResource::collection($keywords);
    }
}
