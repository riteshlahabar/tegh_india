<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    public const STATUSES = ['Overdue', 'Due Today', 'Promise Given', 'Paid', 'Not Due'];

    public const AGING_BUCKETS = [
        '0-7 Days',
        '8-15 Days',
        '16-30 Days',
        '31-60 Days',
        '61-90 Days',
        '90+ Days',
    ];

    protected $appends = ['computed_status', 'days_overdue', 'aging_bucket', 'narration'];

    protected $fillable = [
        'party_id',
        'invoice_no',
        'po_no',
        'narration',
        'invoice_date',
        'due_date',
        'invoice_amount',
        'received_amount',
        'outstanding',
        'status',
        'priority',
        'sales_person',
        'branch',
        'assigned_to',
        'last_followup_at',
        'next_followup_on',
        'busy_invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'next_followup_on' => 'date',
            'last_followup_at' => 'datetime',
            'invoice_amount' => 'float',
            'received_amount' => 'float',
            'outstanding' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('busy_source', function (Builder $query) {
            $query->whereNotNull('busy_invoice_id')
                ->where('busy_invoice_id', 'not like', 'BINV-%');
        });
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    public function promises(): HasMany
    {
        return $this->hasMany(PaymentPromise::class);
    }

    public function activePromise(): HasOne
    {
        return $this->hasOne(PaymentPromise::class)
            ->whereNull('collected_at')
            ->whereNotIn('status', ['broken', 'collected'])
            ->latest('id');
    }

    public function getDaysOverdueAttribute(): int
    {
        if (! $this->due_date) {
            return 0;
        }

        return (int) $this->due_date->startOfDay()->diffInDays(Carbon::today(), false);
    }

    public function getAgingBucketAttribute(): string
    {
        $days = max($this->days_overdue, 0);
        if ($days <= 7) {
            return '0-7 Days';
        }
        if ($days <= 15) {
            return '8-15 Days';
        }
        if ($days <= 30) {
            return '16-30 Days';
        }
        if ($days <= 60) {
            return '31-60 Days';
        }
        if ($days <= 90) {
            return '61-90 Days';
        }

        return '90+ Days';
    }

    public function getComputedStatusAttribute(): string
    {
        if ((float) $this->outstanding <= 0) {
            return 'Paid';
        }

        if ($this->relationLoaded('activePromise') ? $this->activePromise : $this->activePromise()->exists()) {
            return 'Promise Given';
        }

        $days = $this->days_overdue;
        if ($days > 0) {
            return 'Overdue';
        }
        if ($days === 0) {
            return 'Due Today';
        }

        return 'Not Due';
    }

    public function refreshStoredStatus(): void
    {
        $this->status = $this->computed_status;
        $this->priority = $this->outstanding > 300000 ? 'high' : ($this->outstanding > 100000 ? 'med' : 'low');
        $this->save();
    }

    public function getNarrationAttribute(): ?string
    {
        return $this->attributes['narration'] ?? $this->attributes['po_no'] ?? null;
    }

    public function toActionArray(): array
    {
        $this->loadMissing(['party', 'activePromise']);

        return [
            'id' => $this->id,
            'party' => $this->party?->name,
            'code' => $this->party?->code,
            'contact' => $this->party?->contact_person,
            'mobile' => $this->party?->mobile,
            'email' => $this->party?->email,
            'city' => $this->party?->city,
            'state' => $this->party?->state,
            'invoice_no' => $this->invoice_no,
            'narration' => $this->narration,
            'po_no' => $this->po_no,
            'outstanding' => $this->outstanding,
            'outstanding_inr' => inr($this->outstanding),
            'due_date' => optional($this->due_date)->toDateString(),
            'due_date_label' => crm_date($this->due_date),
            'promise_amount' => $this->activePromise?->amount ?? $this->outstanding,
            'promise_date' => optional($this->activePromise?->promise_date)->toDateString(),
        ];
    }
}
