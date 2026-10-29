<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Party extends Model
{
    protected $fillable = [
        'name',
        'code',
        'contact_person',
        'mobile',
        'email',
        'gstin',
        'city',
        'state',
        'sales_person',
        'branch',
        'assigned_to',
        'credit_limit',
        'payment_terms',
        'busy_party_id',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('busy_source', function (Builder $query) {
            $query->whereNotNull('busy_party_id')
                ->where('busy_party_id', 'not like', 'BUSY-%');
        });
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    public function totalOutstanding(): float
    {
        return (float) $this->invoices()->sum('outstanding');
    }

    public function callableInvoice(): ?Invoice
    {
        $invoices = $this->relationLoaded('invoices')
            ? $this->invoices
            : $this->invoices()->get();

        return $invoices
            ->filter(fn (Invoice $invoice) => (float) $invoice->outstanding > 0)
            ->sortByDesc(fn (Invoice $invoice) => sprintf('%d-%012.2f', $invoice->days_overdue > 0 ? 1 : 0, (float) $invoice->outstanding))
            ->first();
    }

    public function rollupStatus(): string
    {
        $statuses = $this->invoices->pluck('computed_status');
        if ($statuses->contains('Overdue')) {
            return 'Overdue';
        }
        if ($statuses->contains('Promise Given')) {
            return 'Promise Given';
        }
        if ($statuses->every(fn ($s) => $s === 'Paid')) {
            return 'Paid';
        }

        return 'Not Due';
    }
}
