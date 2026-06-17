<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cities extends Model
{
    //
    public function provinces()
    {
        return $this->belongsTo(Provinces::class);
    }
}
