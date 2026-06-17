<?php

namespace App\Models\Traits;

// use App\Models\SeoMeta;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @property string $status
 */
trait HasStatus
{
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }
}