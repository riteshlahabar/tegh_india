<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusySyncLog extends Model
{
    protected $fillable = [
        'triggered_by',
        'company',
        'status',
        'parties_upserted',
        'invoices_upserted',
        'invoices_closed',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'parties_upserted' => 'integer',
            'invoices_upserted' => 'integer',
            'invoices_closed' => 'integer',
        ];
    }
}
