<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BlogPostResource extends JsonResource
{
    public function toArray($request)
    {
        // Safely handle author name
        $authorName = 'Unknown Author';
        if ($this->author) {
            if ($this->author->first_name && $this->author->last_name) {
                $authorName = $this->author->first_name . ' ' . $this->author->last_name;
            } elseif ($this->author->first_name) {
                $authorName = $this->author->first_name;
            } else {
                $authorName = $this->author->username ?? $this->author->email ?? 'Unknown Author';
            }
        }

        // Safely handle tags - ensure it's always an array
        $tags = [];
        if (!empty($this->tags)) {
            if (is_array($this->tags)) {
                $tags = $this->tags;
            } elseif (is_string($this->tags)) {
                try {
                    $tags = json_decode($this->tags, true) ?? [];
                } catch (\Exception $e) {
                    $tags = [];
                }
            }
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'author' => $authorName,
            'category' => $this->category?->name ?? 'Uncategorized',
            'status' => $this->is_published ? 'published' : ($this->published_at ? 'scheduled' : 'draft'),
            'featured_image' => $this->getFirstMediaUrl('featured_image') ?: null,
            'publish_date' => $this->published_at?->format('Y-m-d') ?? $this->created_at->format('Y-m-d'),
            'views' => $this->views_count ?? 0,
            'tags' => $tags,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),

            // Full relationships for admin use
            'author_full' => new UserResource($this->whenLoaded('author')),
            'category_full' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}