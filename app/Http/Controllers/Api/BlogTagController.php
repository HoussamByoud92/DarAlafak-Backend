<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogTagResource;
use App\Models\BlogTag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BlogTagController extends Controller
{
    /**
     * Display a listing of blog tags.
     */
    public function index(Request $request)
    {
        $query = BlogTag::query();

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('name', 'ILIKE', '%' . $search . '%');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Include posts count
        $query->withCount('posts');

        // Pagination or all
        if ($request->has('per_page')) {
            $tags = $query->paginate($request->get('per_page', 10));
        } else {
            $tags = $query->get();
        }

        return BlogTagResource::collection($tags);
    }

    /**
     * Store a newly created blog tag.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:blog_tags,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tag = BlogTag::create($request->only(['name']));

        return response()->json([
            'message' => 'Blog tag created successfully',
            'data' => new BlogTagResource($tag),
        ], 201);
    }

    /**
     * Display the specified blog tag.
     */
    public function show(BlogTag $blogTag)
    {
        $blogTag->loadCount('posts');
        return new BlogTagResource($blogTag);
    }

    /**
     * Update the specified blog tag.
     */
    public function update(Request $request, BlogTag $blogTag): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:blog_tags,name,' . $blogTag->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $blogTag->update($request->only(['name']));

        return response()->json([
            'message' => 'Blog tag updated successfully',
            'data' => new BlogTagResource($blogTag),
        ]);
    }

    /**
     * Remove the specified blog tag.
     */
    public function destroy(BlogTag $blogTag): JsonResponse
    {
        $blogTag->delete();

        return response()->json(['message' => 'Blog tag deleted successfully']);
    }

    /**
     * Get popular tags (by posts count).
     */
    public function popular(Request $request)
    {
        $limit = $request->get('limit', 10);

        $tags = BlogTag::withCount('posts')
            ->orderByDesc('posts_count')
            ->limit($limit)
            ->get();

        return BlogTagResource::collection($tags);
    }
}
