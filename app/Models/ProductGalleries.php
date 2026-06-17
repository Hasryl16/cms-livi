<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductGalleries extends Model
{
    //
    protected $fillable = [
        'product_id',
        'media_path',
        'sort_order',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
