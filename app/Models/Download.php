<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    //
    protected $fillable = [
        'title',
        'slug',
        'description',
        'file_path',
        'thumbnail',
        'version',
        'download_count',
        'author_id',
    ];

    public function author()
    {
        return $this->belongsTo(
            User::class,
            'author_id'
        );
    }

    public function categories()
    {
        return $this->belongsToMany(
            DownloadCategory::class,
            'download_category_download'
        );
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('is_active', true);
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
