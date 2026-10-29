<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    public const STAGES = [
        'New',
        'Follow-up',
        'Quotation Sent',
        'Negotiation',
        'Won',
        'Lost',
    ];

    protected $fillable = [
        'name',
        'enquiry_product_name',
        'house_wire_discount',
        'house_wire_discount_note',
        'flexible_wire_discount',
        'flexible_wire_discount_note',
        'other_wire_discount',
        'other_wire_discount_note',
        'enquiry_admin_note',
        'contact_person_name',
        'mobile_number',
        'alternate_mobile',
        'whatsapp_number',
        'email',
        'industry',
        'brand',
        'city',
        'state',
        'pin_code',
        'sub_city',
        'address',
        'latitude',
        'longitude',
        'gst_number',
        'credit_limit',
        'payment_terms',
        'outstanding_amount',
        'stage',
        'assigned_exec_id',
        'assigned_tc_id',
        'created_by',
    ];

    protected $casts = [
        'house_wire_discount' => 'boolean',
        'flexible_wire_discount' => 'boolean',
        'other_wire_discount' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function calls()
    {
        return $this->hasMany(Call::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function executive()
    {
        return $this->belongsTo(User::class, 'assigned_exec_id');
    }

    public function telecaller()
    {
        return $this->belongsTo(User::class, 'assigned_tc_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'company_product')->withTimestamps();
    }

    /**
     * How this lead entered the CRM:
     * - Admin created → "Admin Assign"
     * - Sales executive created → "Self" + their name
     */
    public function leadOriginLabel(): string
    {
        $creator = $this->creator;
        if (! $creator) {
            return '—';
        }

        if ($creator->role === 'admin') {
            return 'Admin Assign';
        }

        if ($creator->role === 'sales_executive' || $creator->role === 'telecaller') {
            return 'Self · '.$creator->name;
        }

        return $creator->name;
    }

    public function leadOriginType(): string
    {
        $creator = $this->creator;
        if (! $creator) {
            return 'unknown';
        }

        if ($creator->role === 'admin') {
            return 'admin_assign';
        }

        if ($creator->role === 'sales_executive' || $creator->role === 'telecaller') {
            return 'self';
        }

        return 'other';
    }

    /** Enquiry products shown for salesperson-created leads. */
    public function showsEnquiryProduct(): bool
    {
        if ($this->leadOriginType() !== 'self') {
            return false;
        }

        if ($this->relationLoaded('products') && $this->products->isNotEmpty()) {
            return true;
        }

        return filled($this->enquiry_product_name);
    }

    public function enquiryProductsLabel(): string
    {
        if ($this->relationLoaded('products') && $this->products->isNotEmpty()) {
            return $this->products->pluck('name')->filter()->implode(', ');
        }

        return (string) ($this->enquiry_product_name ?? '');
    }
}
