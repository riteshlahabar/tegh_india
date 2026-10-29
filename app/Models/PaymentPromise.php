<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentPromise extends Model
{
    protected $fillable = [
        'party_id',
        'invoice_id',
        'telecaller_id',
        'amount',
        'promise_date',
        'collected_at',
        'status',
    ];

    protected $appends = ['display_status'];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'promise_date' => 'date',
            'collected_at' => 'datetime',
        ];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function telecaller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'telecaller_id');
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->collected_at || $this->status === 'collected') {
            return 'Collected';
        }

        $diff = (int) $this->promise_date->startOfDay()->diffInDays(Carbon::today(), false);
        if ($diff === 0) {
            return 'Due Today';
        }
        if ($diff > 0) {
            return 'Broken Promise';
        }

        return 'Upcoming';
    }
}
