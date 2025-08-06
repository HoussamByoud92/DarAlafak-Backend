<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Catalogue;
use App\Http\Resources\CatalogueResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class CatalogueController extends Controller
{
    public function index(Request $request)
    {
        $query = Catalogue::active();

        // Search functionality
        if ($request->has('search')) {
            $query->where('name', 'ILIKE', '%' . $request->search . '%')
                  ->orWhere('description', 'ILIKE', '%' . $request->search . '%');
        }

        // Filter by file type
        if ($request->has('file_type')) {
            $query->where('file_type', $request->file_type);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $catalogues = $query->paginate($request->get('per_page', 12));

        return CatalogueResource::collection($catalogues);
    }

    public function show($slug)
    {
        $catalogue = Catalogue::where('slug', $slug)->active()->firstOrFail();
        return new CatalogueResource($catalogue);
    }

    public function download(Catalogue $catalogue)
    {
        if (!$catalogue->is_active) {
            return response()->json(['error' => 'Catalogue not available'], 404);
        }

        if (!Storage::exists($catalogue->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        // Increment download count
        $catalogue->increment('download_count');

        $filePath = Storage::path($catalogue->file_path);
        $fileName = $catalogue->name . '.' . pathinfo($catalogue->file_path, PATHINFO_EXTENSION);

        return response()->download($filePath, $fileName, [
            'Content-Type' => Storage::mimeType($catalogue->file_path),
        ]);
    }

    public function store(Request $request)
    {
        // Check authorization
        if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:10240', // 10MB max
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $filePath = $file->store('catalogues', 'public');

        $catalogue = Catalogue::create([
            'name' => $request->name,
            'description' => $request->description,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'file_type' => $file->getClientMimeType(),
            'is_active' => $request->get('is_active', true),
        ]);

        return new CatalogueResource($catalogue);
    }

    public function update(Request $request, Catalogue $catalogue)
    {
        // Check authorization
        if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:10240',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = $request->only(['name', 'description', 'is_active']);

        if ($request->hasFile('file')) {
            // Delete old file
            if (Storage::exists($catalogue->file_path)) {
                Storage::delete($catalogue->file_path);
            }

            // Store new file
            $file = $request->file('file');
            $filePath = $file->store('catalogues', 'public');

            $updateData['file_path'] = $filePath;
            $updateData['file_size'] = $file->getSize();
            $updateData['file_type'] = $file->getClientMimeType();
        }

        $catalogue->update($updateData);

        return new CatalogueResource($catalogue);
    }

    public function destroy(Catalogue $catalogue)
    {
        // Check authorization
        if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Delete file from storage
        if (Storage::exists($catalogue->file_path)) {
            Storage::delete($catalogue->file_path);
        }

        $catalogue->delete();
        return response()->json(['message' => 'Catalogue deleted successfully']);
    }

    public function popular(Request $request)
    {
        $catalogues = Catalogue::active()
            ->orderBy('download_count', 'desc')
            ->limit($request->get('limit', 5))
            ->get();

        return CatalogueResource::collection($catalogues);
    }

    public function statistics(Request $request)
    {
        // Check authorization
        if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = [
            'total_catalogues' => Catalogue::count(),
            'active_catalogues' => Catalogue::active()->count(),
            'total_downloads' => Catalogue::sum('download_count'),
            'most_downloaded' => Catalogue::orderBy('download_count', 'desc')->first(),
            'file_types' => Catalogue::selectRaw('file_type, COUNT(*) as count')
                ->groupBy('file_type')
                ->get(),
        ];

        return response()->json(['data' => $stats]);
    }
}
