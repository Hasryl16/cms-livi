<?php

namespace App\Models;

//use App\Models\AIMessage;
//use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AIConversation extends Model
{
    //
    protected $table = 'ai_conversations';

    public function messages()
    {
        return $this->hasMany(AIMessages::class, 'conversation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
