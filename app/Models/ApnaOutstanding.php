<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApnaOutstanding extends Model
{
    protected $fillable = [
        'erp_code',
        'account_name',
        'invoice_no',
        'invoice_date',
        'due_date',
        'invoice_amount',
        'outstanding_amount',
        'synced_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'invoice_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
        'synced_at' => 'datetime',
    ];
}
