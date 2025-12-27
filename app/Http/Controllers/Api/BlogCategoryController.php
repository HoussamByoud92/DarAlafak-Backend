<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogCategoryResource;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BlogCategoryController extends Controller
{
    /**
     * Display a listing of blog categories.
     */
    public function index(Request $request)
    {
        $query = BlogCategory::query();

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', '%' . $search . '%')
                    ->orWhere('description', 'ILIKE', '%' . $search . '%');
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Include posts count
        $query->withCount('posts');

        // Pagination or all
        if ($request->has('per_page')) {
            $categories = $query->paginate($request->get('per_page', 10));
        } else {
            $categories = $query->get();
        }

        return BlogCategoryResource::collection($categories);
    }

    /**
     * Store a newly created blog category.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $category = BlogCategory::create($request->only(['name', 'description', 'color']));

        return response()->json([
            'message' => 'Blog category created successfully',
            'data' => new BlogCategoryResource($category),
        ], 201);
    }

    /**
     * Display the specified blog category.
     */
    public function show(BlogCategory $blogCategory)
    {
        $blogCategory->loadCount('posts');
        return new BlogCategoryResource($blogCategory);
    }

    /**
     * Update the specified blog category.
     */
    public function update(Request $request, BlogCategory $blogCategory): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $blogCategory->update($request->only(['name', 'description', 'color']));

        return response()->json([
            'message' => 'Blog category updated successfully',
            'data' => new BlogCategoryResource($blogCategory),
        ]);
    }

    /**
     * Remove the specified blog category.
     */
    public function destroy(BlogCategory $blogCategory): JsonResponse
    {
        // Check if category has posts
        if ($blogCategory->posts()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete category with existing posts',
                'posts_count' => $blogCategory->posts()->count(),
            ], 422);
        }

        $blogCategory->delete();

        return response()->json(['message' => 'Blog category deleted successfully']);
    }
}
