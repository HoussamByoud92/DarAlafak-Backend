<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'isbn' => $this->isbn,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'final_price' => $this->final_price,
            'discount_percentage' => $this->discount_percentage,
            'pages' => $this->pages,
            'description' => $this->description,
            'summary' => $this->summary,
            'front_image' => $this->getFirstMediaUrl('front_cover') ?: null,
            'back_image'  => $this->getFirstMediaUrl('back_cover') ?: null,
            'is_published' => $this->is_published,
            'is_available' => $this->is_available,
            'is_featured' => $this->is_featured,
            'stock_quantity' => $this->stock_quantity,
            'weight' => $this->weight,
            'dimensions' => $this->dimensions,
            'language' => $this->language,
            'publication_date' => $this->publication_date?->format('Y-m-d'),
            'edition' => $this->edition,
            'views_count' => $this->views_count,
            'sales_count' => $this->sales_count,
            'rating' => $this->rating,
            'reviews_count' => $this->reviews_count,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            // Relationships
            'category' => new CategoryResource($this->whenLoaded('category')),
            'publisher' => new PublisherResource($this->whenLoaded('publisher')),
            'serie' => new SerieResource($this->whenLoaded('serie')),
            'physical_format' => new PhysicalFormatResource($this->whenLoaded('physicalFormat')),
            'authors' => AuthorResource::collection($this->whenLoaded('authors')),
            'keywords' => KeywordResource::collection($this->whenLoaded('keywords')),
            'reviews' => BookReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
