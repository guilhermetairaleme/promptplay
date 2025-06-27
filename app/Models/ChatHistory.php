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
    ];

    protected $casts = [
        'fields' => 'array',
    ];
}
