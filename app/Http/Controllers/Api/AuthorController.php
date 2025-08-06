<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Http\Resources\AuthorResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthorController extends Controller
{
    public function index(Request $request)
    {
        $query = Author::active();

        // Search functionality
        if ($request->has('search')) {
            $query->where('name', 'ILIKE', '%' . $request->search . '%');
        }

        // Filter by nationality
        if ($request->has('nationality')) {
            $query->where('nationality', $request->nationality);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $authors = $query->paginate($request->get('per_page', 15));

        return AuthorResource::collection($authors);
    }

    public function show($slug)
    {
        $author = Author::where('slug', $slug)
            ->with(['books' => function ($query) {
                $query->published()->available()->with(['category', 'publisher']);
            }])
            ->firstOrFail();

        return new AuthorResource($author);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'biography' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'death_date' => 'nullable|date|after:birth_date',
            'nationality' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:500',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $author = Author::create($request->except('photo'));

        if ($request->hasFile('photo')) {
            $author->addMediaFromRequest('photo')
                ->toMediaCollection('photo');
        }

        return new AuthorResource($author);
    }

    public function update(Request $request, Author $author)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'biography' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'death_date' => 'nullable|date|after:birth_date',
            'nationality' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:500',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $author->update($request->except('photo'));

        if ($request->hasFile('photo')) {
            $author->clearMediaCollection('photo');
            $author->addMediaFromRequest('photo')
                ->toMediaCollection('photo');
        }

        return new AuthorResource($author);
    }

    public function destroy(Author $author)
    {
        // Check if author has books
        if ($author->books()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete author with associated books'
            ], 400);
        }

        $author->delete();
        return response()->json(['message' => 'Author deleted successfully']);
    }

    public function books($slug, Request $request)
    {
        $author = Author::where('slug', $slug)->firstOrFail();
        
        $books = $author->books()
            ->published()
            ->available()
            ->with(['category', 'publisher', 'authors'])
            ->paginate($request->get('per_page', 12));

        return response()->json([
            'author' => new AuthorResource($author),
            'books' => $books
        ]);
    }
}
