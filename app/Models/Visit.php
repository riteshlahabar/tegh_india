<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'executive_id',
        'checked_in_at',
        'checked_out_at',
        'latitude',
        'longitude',
        'proof_photo_path',
        'notes',
        'discount_amount',
        'discount_note',
        'status',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'discount_amount' => 'float',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function executive()
    {
        return $this->belongsTo(User::class, 'executive_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'visit_product')->withTimestamps();
    }
}
