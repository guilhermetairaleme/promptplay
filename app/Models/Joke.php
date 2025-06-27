<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Joke extends Model
{
  protected $table = 'jokes';

    protected $fillable = [
        'joke',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];
}
