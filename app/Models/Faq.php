<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = [
        'category_id',
        'author_id',
        'question',
        'answer',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(
            FaqCategory::class,
            'category_id'
        );
    }

    public function author()
    {
        return $this->belongsTo(
            User::class,
            'author_id'
        );
    }
}