<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    protected $table = 'tokens';

    protected $fillable = [
        'access_token',
        'token_type',
        'expires_in',
        'scope',
        'jti',
    ];
}
