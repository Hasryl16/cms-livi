<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provinces extends Model
{
    //
    public function countries()
    {
        return $this->belongsTo(Countries::class);
    }

    public function cities()
    {
        return $this->hasMany(Cities::class);
    }
}
