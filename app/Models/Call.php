<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Call extends Model
{
    public const OUTCOMES = [
        'Connected',
        'Not Connected',
        'Busy',
        'Call Later',
        'Wrong Number',
        'Payment Confirmed',
        'Payment Promise',
        'Dispute',
        'Cheque Given',
        'NEFT/RTGS Done',
    ];

    protected $fillable = [
        'party_id',
        'invoice_id',
        'telecaller_id',
        'called_at',
        'outcome',
        'remarks',
        'next_followup_on',
        'promise_amount',
        'promise_date',
    ];

    protected function casts(): array
    {
        return [
            'called_at' => 'datetime',
            'next_followup_on' => 'date',
            'promise_date' => 'date',
            'promise_amount' => 'float',
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
}
