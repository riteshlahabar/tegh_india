<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApnaItem extends Model
{
    protected $fillable = [
        'erp_code',
        'name',
        'alias',
        'print_name',
        'parent_group',
        'unit',
        'hsn_code',
        'sale_price',
        'mrp',
        'closing_stock',
        'tax_percentage',
        'master_status',
        'synced_at',
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'mrp' => 'decimal:2',
        'closing_stock' => 'decimal:3',
        'tax_percentage' => 'decimal:2',
        'synced_at' => 'datetime',
    ];
}
