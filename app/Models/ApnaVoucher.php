<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApnaVoucher extends Model
{
    protected $fillable = [
        'kind',
        'voucher_id',
        'voucher_no',
        'voucher_type',
        'status',
        'tran_date',
        'amount',
        'narration',
        'synced_at',
    ];

    protected $casts = [
        'tran_date' => 'date',
        'amount' => 'decimal:2',
        'synced_at' => 'datetime',
    ];
}
