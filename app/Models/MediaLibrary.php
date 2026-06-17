<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaLibrary extends Model
{
    protected $fillable = [
        'folder_id',
        'collection_name',
        'file_name',
        'disk',
        'mime_type',
        'media_type',
        'size',
        'path',
        'custom_properties',
        'alt_text',
        'title',
        'caption',
        'uploaded_by',
    ];

    protected $casts = [
        'custom_properties' => 'array',
    ];

    public function folder()
    {
        return $this->belongsTo(MediaFolder::class);
    }

    public function uploader()
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }
}