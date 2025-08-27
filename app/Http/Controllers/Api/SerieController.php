<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Serie;
use App\Http\Resources\SerieResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SerieController extends Controller
{
    public function index(Request $request)
    {
        $query = Serie::active();

        // Search functionality
        if ($request->has('search')) {
            $query->where('name', 'ILIKE', '%' . $request->search . '%');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $series = $query->paginate($request->get('per_page', 15));

        return SerieResource::collection($series);
    }

    public function show($slug)
    {
        $serie = Serie::where('slug', $slug)
            ->with(['books' => function ($query) {
                $query->published()->available()->with(['category', 'authors', 'publisher']);
            }])
            ->firstOrFail();

        return new SerieResource($serie);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $serie = Serie::create($request->except('photo'));

        if ($request->hasFile('photo')) {
            $serie->addMediaFromRequest('photo')
                ->toMediaCollection('photo');
        }

        return new SerieResource($serie);
    }

    public function update(Request $request, $id)
    {
        $serie = Serie::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'photo' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $serie->update($request->except('photo'));

        if ($request->hasFile('photo')) {
            $serie->clearMediaCollection('photo');
            $serie->addMediaFromRequest('photo')->toMediaCollection('photo');
        }

        return new SerieResource($serie->fresh());
    }




    public function destroy(Serie $serie)
    {
        // Check if serie has books
        if ($serie->books()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete series with associated books'
            ], 400);
        }

        $serie->delete();
        return response()->json(['message' => 'Series deleted successfully']);
    }

    public function books($slug, Request $request)
    {
        $serie = Serie::where('slug', $slug)->firstOrFail();
        
        $books = $serie->books()
            ->published()
            ->available()
            ->with(['category', 'authors', 'publisher'])
            ->paginate($request->get('per_page', 12));

        return response()->json([
            'serie' => new SerieResource($serie),
            'books' => $books
        ]);
    }
}
