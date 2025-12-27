<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogPostResource;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller
{
    /**
     * Display a listing of blog posts.
     * Public endpoint - only shows published posts.
     */
    public function index(Request $request)
    {
        $query = BlogPost::with(['author', 'category']);

        // Only show published posts for public requests
        $isAdminRequest = $request->is('admin/*') || $request->is('api/admin/*');

        if (!$isAdminRequest) {
            $query->published();
        }

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', '%' . $search . '%')
                    ->orWhere('content', 'ILIKE', '%' . $search . '%')
                    ->orWhere('excerpt', 'ILIKE', '%' . $search . '%');
            });
        }

        // Filter by category (ID or slug)
        if ($request->has('category') && !empty($request->category)) {
            $category = $request->category;
            if (is_numeric($category)) {
                $query->where('category_id', $category);
            } else {
                $query->whereHas('category', function ($q) use ($category) {
                    $q->where('slug', $category);
                });
            }
        }

        // Filter featured posts
        if ($request->has('featured')) {
            $query->where('is_featured', $request->boolean('featured'));
        }

        // Filter by status for admin
        if ($isAdminRequest && $request->has('status')) {
            switch ($request->status) {
                case 'published':
                    $query->where('is_published', true);
                    break;
                case 'draft':
                    $query->where('is_published', false);
                    break;
            }
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'published_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Handle null published_at for sorting
        if ($sortBy === 'published_at') {
            $query->orderByRaw('published_at IS NULL, published_at ' . $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = min($request->get('per_page', 12), 100);
        $posts = $query->paginate($perPage);

        return BlogPostResource::collection($posts)->additional([
            'current_page' => $posts->currentPage(),
            'last_page' => $posts->lastPage(),
            'per_page' => $posts->perPage(),
            'total' => $posts->total(),
        ]);
    }

    /**
     * Display the specified blog post.
     */
    public function show(string $slug)
    {
        $post = BlogPost::where('slug', $slug)
            ->with(['author', 'category'])
            ->firstOrFail();

        // Only allow viewing unpublished posts for admin users
        if (!$post->is_published) {
            if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
                return response()->json(['error' => 'Post not found'], 404);
            }
        }

        // Increment views count
        $post->increment('views_count');

        return new BlogPostResource($post);
    }

    /**
     * Store a newly created blog post.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'category_id' => 'nullable|exists:blog_categories,id',
            'is_published' => 'nullable',
            'is_featured' => 'nullable',
            'published_at' => 'nullable|date',
            'featured_image' => 'nullable|image|max:2048',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
        ]);

        // Convert string booleans to actual booleans
        $validated['is_published'] = filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN);
        $validated['is_featured'] = filter_var($request->is_featured, FILTER_VALIDATE_BOOLEAN);

        // Auto-set author from authenticated user
        $validated['author_id'] = auth()->id();

        // Handle file upload
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')
                ->store('blog', 'public');
        }

        // Set published_at if publishing
        if ($validated['is_published'] && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        // Handle tags - default to empty array if not provided
        $validated['tags'] = $validated['tags'] ?? [];

        $post = BlogPost::create($validated);

        return new BlogPostResource($post->load(['category', 'author']));
    }

    /**
     * Update the specified blog post.
     */
    public function update(Request $request, BlogPost $blog)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'sometimes|required|string',
            'category_id' => 'nullable|exists:blog_categories,id',
            'is_published' => 'nullable',
            'is_featured' => 'nullable',
            'published_at' => 'nullable|date',
            'featured_image' => 'nullable|image|max:2048',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
        ]);

        // Convert string booleans
        if ($request->has('is_published')) {
            $validated['is_published'] = filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN);
        }
        if ($request->has('is_featured')) {
            $validated['is_featured'] = filter_var($request->is_featured, FILTER_VALIDATE_BOOLEAN);
        }

        // Handle file upload
        if ($request->hasFile('featured_image')) {
            // Delete old image
            if ($blog->featured_image) {
                Storage::disk('public')->delete($blog->featured_image);
            }
            $validated['featured_image'] = $request->file('featured_image')
                ->store('blog', 'public');
        }

        // Set published_at if publishing for first time
        if (($validated['is_published'] ?? false) && !$blog->published_at) {
            $validated['published_at'] = now();
        }

        $blog->update($validated);

        return new BlogPostResource($blog->load(['category', 'author']));
    }

    /**
     * Remove the specified blog post.
     */
    public function destroy(BlogPost $blog): JsonResponse
    {
        try {
            // Delete featured image if exists
            if ($blog->featured_image) {
                Storage::disk('public')->delete($blog->featured_image);
            }

            $blog->delete();

            return response()->json(['message' => 'Blog post deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete post',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get featured blog posts.
     */
    public function featured(Request $request)
    {
        $limit = $request->get('limit', 5);

        $posts = BlogPost::published()
            ->featured()
            ->with(['author', 'category'])
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();

        return BlogPostResource::collection($posts);
    }

    /**
     * Get recent blog posts.
     */
    public function recent(Request $request)
    {
        $limit = $request->get('limit', 5);

        $posts = BlogPost::published()
            ->with(['author', 'category'])
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();

        return BlogPostResource::collection($posts);
    }

    /**
     * Get related blog posts.
     */
    public function related(BlogPost $post, Request $request)
    {
        $limit = $request->get('limit', 3);

        $relatedPosts = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->where(function ($query) use ($post) {
                // Same category
                if ($post->category_id) {
                    $query->where('category_id', $post->category_id);
                }
                // Or same author
                $query->orWhere('author_id', $post->author_id);
            })
            ->with(['author', 'category'])
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();

        return BlogPostResource::collection($relatedPosts);
    }
}
