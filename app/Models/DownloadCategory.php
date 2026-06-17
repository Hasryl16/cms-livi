<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DownloadCategory extends Model
{
    //
    protected $fillable = [
        'name',
        'slug',
        'sort_order',
    ];

    public function downloads()
    {
        return $this->belongsToMany(
            Download::class,
            'download_category_download'
        );
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
