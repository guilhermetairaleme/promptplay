<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $fillable = ['title', 'messages','joke','fields','prompt','final_prompt'];

    protected $casts = [
        'messages' => 'array',
    ];
}
