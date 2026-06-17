<?php

namespace App\Models\Traits;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @mixin Model
 */
trait HasSeo
{
    public function seoMeta(): MorphOne
    {
        return $this->morphOne(
            SeoMeta::class,
            'model'
        );
    }
}