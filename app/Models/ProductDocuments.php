<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDocuments extends Model
{
    //
     protected $fillable = [
        'product_id',
        'title',
        'file_path',
        'document_type',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
