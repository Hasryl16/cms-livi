<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaFolder extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'created_by',
    ];

    public function parent()
    {
        return $this->belongsTo(
            MediaFolder::class,
            'parent_id'
        );
    }

    public function children()
    {
        return $this->hasMany(
            MediaFolder::class,
            'parent_id'
        );
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function media()
    {
        return $this->hasMany(MediaLibrary::class);
    }
}