<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApnaSyncLog extends Model
{
    protected $fillable = [
        'endpoint',
        'status',
        'record_count',
        'message',
    ];
}
