<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //
        protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'featured_image',
        'short_description',
        'description',
        'status',
        'featured',
        'published_at',
    ];

    protected $casts = [
        'featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function specifications()
    {
        return $this->hasMany(ProductSpecification::class);
    }

    public function galleries()
    {
        return $this->hasMany(ProductGalleries::class);
    }

    public function documents()
    {
        return $this->hasMany(ProductDocuments::class);
    }

    public function industries()
    {
        return $this->belongsToMany(
            Industry::class,
            'product_industries'
        )->withTimestamps();
    }

    public function seoMeta()
    {
        return $this->morphOne(
            SeoMeta::class,
            'seoable'
        );
    }

}
