<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // public function articles()
    // {
    //     return $this->hasMany(
    //         Article::class,
    //         'category_id'
    //     );
    // }

    public function articles()
    {
        return $this->belongsToMany(
            Article::class,
            'article_category_article'
        );
    }
}