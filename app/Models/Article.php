<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasSeo;
use App\Models\Traits\HasStatus;
use App\Filament\Forms\Components\SeoMetaSection;

class Article extends Model
{
    use HasSeo;
    use HasStatus;

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    // public function category()
    // {
    //     return $this->belongsTo(
    //         ArticleCategory::class,
    //         'category_id'
    //     );
    // }
    public function categories()
    {
        return $this->belongsToMany(
            ArticleCategory::class,
            'article_category_article'
        );
    }

    public function author()
    {
        return $this->belongsTo(
            User::class,
            'author_id'
        );
    }

    public function tags()
    {
        return $this->belongsToMany(
            Tag::class
        );
    }

    public function seoMeta()
    {
        return $this->morphOne(
            SeoMeta::class,
            'seoable'
        );
    }
}

