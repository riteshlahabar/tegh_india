<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApnaAccount extends Model
{
    protected $fillable = [
        'erp_code',
        'name',
        'alias',
        'print_name',
        'parent_group',
        'mobile',
        'whatsapp',
        'email',
        'gst_number',
        'contact',
        'state',
        'pin_code',
        'address',
        'credit_limit',
        'credit_days',
        'closing_balance',
        'master_status',
        'synced_at',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'synced_at' => 'datetime',
    ];
}
