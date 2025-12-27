<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BlogPostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'featured_image' => $this->featured_image
                ? config('app.url') . Storage::url($this->featured_image)
                : null,
            'author' => $this->whenLoaded('author', function () {
                return [
                    'id' => $this->author->id,
                    'first_name' => $this->author->first_name ?? $this->author->name,
                    'last_name' => $this->author->last_name ?? '',
                    'username' => $this->author->username ?? $this->author->email,
                ];
            }),
            'category' => $this->whenLoaded('category', function () {
                if (!$this->category) {
                    return null;
                }
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                    'color' => $this->category->color,
                ];
            }),
            'tags' => $this->tags ?? [],
            'is_published' => $this->is_published,
            'is_featured' => $this->is_featured,
            'published_at' => $this->published_at?->toIso8601String(),
            'views_count' => $this->views_count ?? 0,
            'read_time' => $this->read_time ?? null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
