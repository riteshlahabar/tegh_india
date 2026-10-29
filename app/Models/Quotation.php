<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'quotation_number',
        'amount',
        'status',
        'date',
        'notes',
        'valid_until',
        'emailed_at',
        'emailed_to',
        'whatsapp_sent_at',
        'whatsapp_sent_to',
        'whatsapp_message',
        'sales_person_name',
        'sales_person_mobile',
        'payment_terms',
        'terms_conditions',
        'product_make',
        'party_gstin',
        'party_mobile',
        'party_address',
        'total_qty',
    ];

    protected $casts = [
        'date' => 'datetime',
        'valid_until' => 'date',
        'emailed_at' => 'datetime',
        'whatsapp_sent_at' => 'datetime',
        'amount' => 'float',
        'total_qty' => 'float',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public static function defaultTerms(): string
    {
        return "G.S.T. @18% Extra\nFreight extra. (Unloading in your scope.)\nValidity :- 2 DAYS\nQty tolerence :-+/-5%";
    }

    public static function nextNumber(): string
    {
        $now = now();
        $fyStart = $now->month >= 4 ? $now->year : $now->year - 1;
        $fyEnd = $fyStart + 1;
        $suffix = substr((string) $fyStart, -2).'-'.substr((string) $fyEnd, -2);

        $seq = static::query()->where('quotation_number', 'like', 'Q-%/'.$suffix)->count() + 1;

        return 'Q-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT).'/'.$suffix;
    }
}
