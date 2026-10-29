<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApnaPendingOrder extends Model
{
    protected $fillable = [
        'item',
        'account_name',
        'order_ref',
        'order_date',
        'due_date',
        'order_qty',
        'pending_qty',
        'synced_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'due_date' => 'date',
        'order_qty' => 'decimal:3',
        'pending_qty' => 'decimal:3',
        'synced_at' => 'datetime',
    ];
}
