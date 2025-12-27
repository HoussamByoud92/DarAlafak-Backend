<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Book extends Model implements HasMedia
{
    use HasFactory, HasSlug, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'title', 'slug', 'isbn', 'price', 'discount_price', 'pages',
        'description', 'summary', 'front_image', 'back_image',
        'is_published', 'is_available', 'is_featured', 'stock_quantity',
        'weight', 'dimensions', 'language', 'publication_date', 'edition',
        'category_id', 'physical_format_id', 'publisher_id', 'serie_id',
        'views_count', 'sales_count', 'rating', 'reviews_count'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'rating' => 'decimal:2',
        'publication_date' => 'date',
        'is_published' => 'boolean',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
    ];


    protected $appends = ['final_price', 'front_image_url', 'back_image_url'];

    // Slug configuration
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    // Media collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('front_cover')->singleFile();
        $this->addMediaCollection('back_cover')->singleFile();
        $this->addMediaCollection('gallery');
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(400)
            ->sharpen(10);

        $this->addMediaConversion('preview')
            ->width(600)
            ->height(800)
            ->sharpen(10);
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }

    public function serie()
    {
        return $this->belongsTo(Serie::class);
    }

    public function physicalFormat()
    {
        return $this->belongsTo(PhysicalFormat::class);
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class, 'book_authors')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function keywords()
    {
        return $this->belongsToMany(Keyword::class, 'book_keywords')
            ->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(BookReview::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // Accessors
    public function getFinalPriceAttribute()
    {
        return ($this->price)-($this->discount_price) ?? $this->price;
    }

    public function getDiscountPercentageAttribute()
    {
        if ($this->discount_price && $this->price > 0) {
            return round((($this->price - $this->discount_price) / $this->price) * 100);
        }
        return 0;
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'price', 'is_published', 'is_available'])
            ->logOnlyDirty();
    }

    // In your Book model (Book.php)
public function getFrontImageUrlAttribute()
{
    if (!$this->front_image) {
        return null;
    }
    
    // If it's already a URL, return it
    if (filter_var($this->front_image, FILTER_VALIDATE_URL)) {
        return $this->front_image;
    }
    
    // If it's a full path, extract just the filename
    if (str_contains($this->front_image, DIRECTORY_SEPARATOR)) {
        $filename = basename($this->front_image);
        return asset('storage/' . $filename);
    }
    
    // Assume it's already a relative path
    return asset('storage/' . $this->front_image);
}

public function getBackImageUrlAttribute()
{
    if (!$this->back_image) {
        return null;
    }
    
    // If it's already a URL, return it
    if (filter_var($this->back_image, FILTER_VALIDATE_URL)) {
        return $this->back_image;
    }
    
    // If it's a full path, extract just the filename
    if (str_contains($this->back_image, DIRECTORY_SEPARATOR)) {
        $filename = basename($this->back_image);
        return asset('storage/' . $filename);
    }
    
    // Assume it's already a relative path
    return asset('storage/' . $this->back_image);
}
}
