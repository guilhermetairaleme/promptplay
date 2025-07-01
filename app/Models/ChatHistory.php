<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatHistory extends Model
{
      protected $fillable = [
        'title',
        'joke',
        'fields',
        'extra',
        'prompt',
        'final_prompt',
        'user_id'
    ];

    protected $casts = [
        'fields' => 'array',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
