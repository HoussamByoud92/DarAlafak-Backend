<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\User;
use App\Http\Resources\BlogPostResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        // For admin, show all posts regardless of status
        $isAdminRequest = $request->is('admin/*') || $request->is('api/admin/*');
        
        $query = BlogPost::with(['author', 'category']);

        // Only show published posts for non-admin requests
        if (!$isAdminRequest) {
            $query->published();
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'ILIKE', '%' . $search . '%')
                  ->orWhere('content', 'ILIKE', '%' . $search . '%')
                  ->orWhere('excerpt', 'ILIKE', '%' . $search . '%');
            });
        }

        // Filter by category ID
        if ($request->has('category') && is_numeric($request->category)) {
            $query->where('category_id', $request->category);
        }

        // Filter by author ID
        if ($request->has('author') && is_numeric($request->author)) {
            $query->where('author_id', $request->author);
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
                    $query->where('is_published', false)->whereNull('published_at');
                    break;
                case 'archived':
                    $query->onlyTrashed();
                    break;
            }
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $posts = $query->paginate($request->get('per_page', 10));

        return BlogPostResource::collection($posts);
    }

    public function show($slug)
    {
        $post = BlogPost::where('slug', $slug)
            ->with(['author', 'category'])
            ->firstOrFail();

        // Only allow viewing published posts unless admin
        if (!$post->is_published && (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser))) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        // Increment views count
        $post->increment('views_count');

        return new BlogPostResource($post);
    }

    public function store(Request $request)
    {
        // Check authorization
        if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:500',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:1000',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:published,draft',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'publish_date' => 'nullable|date',
            'tags' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // Parse tags from JSON string or comma-separated
            $tags = [];
            if ($request->has('tags') && !empty($request->tags)) {
                try {
                    $tags = json_decode($request->tags, true);
                    if (!is_array($tags)) {
                        $tags = array_filter(array_map('trim', explode(',', $request->tags)));
                    }
                } catch (\Exception $e) {
                    $tags = array_filter(array_map('trim', explode(',', $request->tags)));
                }
            }

            $postData = [
                'title' => $request->title,
                'content' => $request->content,
                'excerpt' => $request->excerpt,
                'author_id' => auth()->id(), // Use the authenticated user as author
                'category_id' => $request->category_id,
                'tags' => $tags,
                'is_published' => $request->status === 'published',
                'is_featured' => $request->boolean('is_featured', false),
            ];

            // Handle publish date
            if ($request->status === 'published' && !$request->has('publish_date')) {
                $postData['published_at'] = now();
            } elseif ($request->has('publish_date')) {
                $postData['published_at'] = $request->publish_date;
            }

            $post = BlogPost::create($postData);

            if ($request->hasFile('featured_image')) {
                $post->addMediaFromRequest('featured_image')
                    ->toMediaCollection('featured_image');
            }

            DB::commit();

            return new BlogPostResource($post->load(['author', 'category']));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to create post',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, BlogPost $post)
    {
        // Check authorization
        if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:500',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:1000',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:published,draft',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'publish_date' => 'nullable|date',
            'tags' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // Parse tags
            $tags = [];
            if ($request->has('tags') && !empty($request->tags)) {
                try {
                    $tags = json_decode($request->tags, true);
                    if (!is_array($tags)) {
                        $tags = array_filter(array_map('trim', explode(',', $request->tags)));
                    }
                } catch (\Exception $e) {
                    $tags = array_filter(array_map('trim', explode(',', $request->tags)));
                }
            }

            $postData = [
                'title' => $request->title,
                'content' => $request->content,
                'excerpt' => $request->excerpt,
                'category_id' => $request->category_id,
                'tags' => $tags,
                'is_published' => $request->status === 'published',
                'is_featured' => $request->boolean('is_featured', $post->is_featured),
            ];

            // Handle publish date
            if ($request->status === 'published' && !$post->published_at) {
                $postData['published_at'] = now();
            } elseif ($request->has('publish_date')) {
                $postData['published_at'] = $request->publish_date;
            } elseif ($request->status === 'draft') {
                $postData['published_at'] = null;
            }

            $post->update($postData);

            if ($request->hasFile('featured_image')) {
                $post->clearMediaCollection('featured_image');
                $post->addMediaFromRequest('featured_image')
                    ->toMediaCollection('featured_image');
            }

            DB::commit();

            return new BlogPostResource($post->load(['author', 'category']));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to update post',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(BlogPost $post)
    {
        // Check authorization
        if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $post->delete();
            return response()->json(['message' => 'Blog post deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete post',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function featured(Request $request)
    {
        $posts = BlogPost::published()
            ->featured()
            ->with(['author', 'category'])
            ->latest('published_at')
            ->limit($request->get('limit', 5))
            ->get();

        return BlogPostResource::collection($posts);
    }

    public function recent(Request $request)
    {
        $posts = BlogPost::published()
            ->with(['author', 'category'])
            ->latest('published_at')
            ->limit($request->get('limit', 5))
            ->get();

        return BlogPostResource::collection($posts);
    }

    public function related(BlogPost $post, Request $request)
    {
        $relatedPosts = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->where(function ($query) use ($post) {
                $query->where('category_id', $post->category_id)
                      ->orWhere('author_id', $post->author_id);
            })
            ->with(['author', 'category'])
            ->latest('published_at')
            ->limit($request->get('limit', 3))
            ->get();

        return BlogPostResource::collection($relatedPosts);
    }
}