<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SustainabilityContent extends Model
{
    
    protected $fillable = [
        'title',
        'slug',
        'content',
        'featured_image',
        'author_id',
        'status',
        'published_at',
    ];
    // 
    public function author()
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->belongsToMany(
            SustainabilityCategory::class,
            'sustainability_category_sustainability_content'
        );
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function seoMeta()
    {
        return $this->morphOne(
            SeoMeta::class,
            'seoable'
        );
    }
}
