<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIMessages extends Model
{
    //
    protected $table = 'ai_messages';

    protected $casts = [
        'metadata' => 'array',
    ];

    public function conversation()
    {
        return $this->belongsTo(
            AIConversation::class,
            'conversation_id'
        );
    }
}
