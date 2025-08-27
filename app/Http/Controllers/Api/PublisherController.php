<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Publisher;
use App\Http\Resources\PublisherResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublisherController extends Controller
{
    public function index(Request $request)
    {
        $query = Publisher::active();

        // Search functionality
        if ($request->has('search')) {
            $query->where('name', 'ILIKE', '%' . $request->search . '%');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $publishers = $query->paginate($request->get('per_page', 15));

        return PublisherResource::collection($publishers);
    }

    public function show($slug)
    {
        $publisher = Publisher::where('slug', $slug)
            ->with(['books' => function ($query) {
                $query->published()->available()->with(['category', 'authors']);
            }])
            ->firstOrFail();

        return new PublisherResource($publisher);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'website' => 'nullable|url|max:500',
            'email' => 'nullable|email|max:254',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $publisher = Publisher::create($request->except('logo'));

        if ($request->hasFile('logo')) {
            $publisher->addMediaFromRequest('logo')
                ->toMediaCollection('logo');
        }

        return new PublisherResource($publisher);
    }

    public function update(Request $request, Publisher $publisher)
{
    // Make all fields optional for partial update
    $validator = Validator::make($request->all(), [
        'name'        => 'sometimes|required|string|max:255',
        'description' => 'nullable|string',
        'website'     => 'nullable|url|max:500',
        'email'       => 'nullable|email|max:254',
        'phone'       => 'nullable|string|max:20',
        'address'     => 'nullable|string',
        'logo'        => 'nullable', // don't validate as image unless file is uploaded
        'is_active'   => 'boolean',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Only update fields that were sent in request
    $publisher->fill($validator->validated())->save();
    if ($request->hasFile('logo')) {
    $validator->sometimes('logo', 'image|mimes:jpeg,png,jpg,gif|max:2048', function () {
        return true;
    });
}

    // If logo is a file upload
    if ($request->hasFile('logo')) {
        $publisher->clearMediaCollection('logo');
        $publisher->addMediaFromRequest('logo')
                  ->toMediaCollection('logo');
    }

    return new PublisherResource($publisher);
}

    public function destroy(Publisher $publisher)
    {
        // Check if publisher has books
        if ($publisher->books()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete publisher with associated books'
            ], 400);
        }

        $publisher->delete();
        return response()->json(['message' => 'Publisher deleted successfully']);
    }

    public function books($slug, Request $request)
    {
        $publisher = Publisher::where('slug', $slug)->firstOrFail();
        
        $books = $publisher->books()
            ->published()
            ->available()
            ->with(['category', 'authors'])
            ->paginate($request->get('per_page', 12));

        return response()->json([
            'publisher' => new PublisherResource($publisher),
            'books' => $books
        ]);
    }
}
