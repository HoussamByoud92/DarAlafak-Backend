<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Http\Resources\BlogPostResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = BlogPost::published()->with(['author', 'category']);

        // Search functionality
        if ($request->has('search')) {
            $query->where('title', 'ILIKE', '%' . $request->search . '%')
                  ->orWhere('content', 'ILIKE', '%' . $request->search . '%');
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by author
        if ($request->has('author')) {
            $query->where('author_id', $request->author);
        }

        // Filter featured posts
        if ($request->has('featured')) {
            $query->where('is_featured', $request->boolean('featured'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'published_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $posts = $query->paginate($request->get('per_page', 10));

        return BlogPostResource::collection($posts);
    }

    public function show($slug)
    {
        $post = BlogPost::where('slug', $slug)
            ->published()
            ->with(['author', 'category'])
            ->firstOrFail();

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
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'category_id' => 'nullable|exists:categories,id',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $postData = $request->except('featured_image');
        $postData['author_id'] = auth()->id();

        if ($request->is_published && !$request->has('published_at')) {
            $postData['published_at'] = now();
        }

        $post = BlogPost::create($postData);

        if ($request->hasFile('featured_image')) {
            $post->addMediaFromRequest('featured_image')
                ->toMediaCollection('featured_image');
        }

        return new BlogPostResource($post->load(['author', 'category']));
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
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'category_id' => 'nullable|exists:categories,id',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $postData = $request->except('featured_image');

        // Set published_at if publishing for the first time
        if ($request->is_published && !$post->published_at) {
            $postData['published_at'] = now();
        }

        $post->update($postData);

        if ($request->hasFile('featured_image')) {
            $post->clearMediaCollection('featured_image');
            $post->addMediaFromRequest('featured_image')
                ->toMediaCollection('featured_image');
        }

        return new BlogPostResource($post->load(['author', 'category']));
    }

    public function destroy(BlogPost $post)
    {
        // Check authorization
        if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $post->delete();
        return response()->json(['message' => 'Blog post deleted successfully']);
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
