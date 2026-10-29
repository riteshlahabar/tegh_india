<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApnaGroup extends Model
{
    protected $fillable = [
        'kind',
        'erp_code',
        'name',
        'parent_name',
        'busy_mast_code',
        'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];
}
