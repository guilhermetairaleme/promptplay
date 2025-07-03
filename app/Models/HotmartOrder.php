<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotmartOrder extends Model
{
  protected $fillable = [
        'event',
        'transaction_id',
        'buyer_email',
        'buyer_name',
        'product_name',
        'status',
        'amount',
        'currency',
        'payment_type',
        'commission_total',
        'data_json',
    ];

    protected $casts = [
        'data_json' => 'array',
    ];
}
