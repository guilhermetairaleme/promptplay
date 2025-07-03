<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
      protected $fillable = [
        'external_id',
        'format',
        'warranty_period',
        'status',
        'is_subscription',
        'name',
        'created_at_external',
        'ucode',
    ];
}
