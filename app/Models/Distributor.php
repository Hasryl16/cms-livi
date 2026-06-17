<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Distributor extends Model
{
    //
    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'country_id',
        'province_id',
        'city_id',
        'latitude',
        'longitude',
        'website',
        'is_active',
    ];

    public function country()
    {
        return $this->belongsTo(Countries::class);
    }

    public function province()
    {
        return $this->belongsTo(Provinces::class);
    }

    public function city()
    {
        return $this->belongsTo(Cities::class);
    }
}
