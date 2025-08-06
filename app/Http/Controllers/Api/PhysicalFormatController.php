<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhysicalFormat;
use App\Http\Resources\PhysicalFormatResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PhysicalFormatController extends Controller
{
    public function index(Request $request)
    {
        $query = PhysicalFormat::active();

        // Search functionality
        if ($request->has('search')) {
            $query->where('name', 'ILIKE', '%' . $request->search . '%');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $formats = $query->paginate($request->get('per_page', 15));

        return PhysicalFormatResource::collection($formats);
    }

    public function show($slug)
    {
        $format = PhysicalFormat::where('slug', $slug)
            ->with(['books' => function ($query) {
                $query->published()->available()->with(['category', 'authors', 'publisher']);
            }])
            ->firstOrFail();

        return new PhysicalFormatResource($format);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $format = PhysicalFormat::create($request->all());

        return new PhysicalFormatResource($format);
    }

    public function update(Request $request, PhysicalFormat $physicalFormat)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $physicalFormat->update($request->all());

        return new PhysicalFormatResource($physicalFormat);
    }

    public function destroy(PhysicalFormat $physicalFormat)
    {
        // Check if format has books
        if ($physicalFormat->books()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete format with associated books'
            ], 400);
        }

        $physicalFormat->delete();
        return response()->json(['message' => 'Physical format deleted successfully']);
    }

    public function books($slug, Request $request)
    {
        $format = PhysicalFormat::where('slug', $slug)->firstOrFail();
        
        $books = $format->books()
            ->published()
            ->available()
            ->with(['category', 'authors', 'publisher'])
            ->paginate($request->get('per_page', 12));

        return response()->json([
            'format' => new PhysicalFormatResource($format),
            'books' => $books
        ]);
    }
}
