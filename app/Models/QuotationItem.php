<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'product_id',
        'description',
        'hsn_code',
        'qty',
        'unit',
        'list_price',
        'discount_percent',
        'price',
        'amount',
        'sort_order',
    ];

    protected $casts = [
        'qty' => 'float',
        'list_price' => 'float',
        'discount_percent' => 'float',
        'price' => 'float',
        'amount' => 'float',
        'sort_order' => 'integer',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
