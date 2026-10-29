<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'product_code',
        'usage_unit',
        'description',
        'category',
        'hsn_code',
        'mts',
        'colour',
        'size_sqmm',
        'core',
        'spec',
        'unit',
        'des1',
        'des2',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_product')->withTimestamps();
    }

    /**
     * Typeahead catalog search used by quotation forms.
     *
     * @return array{products: Collection<int, self>, total: int}
     */
    public static function searchCatalog(?string $q = null, int $limit = 40): array
    {
        $limit = max(1, min($limit, 80));
        $query = static::query()->where(function ($inner) {
            $inner->where('is_active', true)->orWhereNull('is_active');
        });

        $term = trim((string) $q);
        if ($term !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('name', 'like', $like)
                    ->orWhere('product_code', 'like', $like)
                    ->orWhere('hsn_code', 'like', $like)
                    ->orWhere('category', 'like', $like)
                    ->orWhere('size_sqmm', 'like', $like)
                    ->orWhere('core', 'like', $like)
                    ->orWhere('spec', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        $total = (clone $query)->count();
        $products = $query->orderBy('name')
            ->limit($limit)
            ->get([
                'id',
                'name',
                'product_code',
                'usage_unit',
                'description',
                'category',
                'hsn_code',
                'unit',
                'size_sqmm',
                'core',
                'spec',
            ]);

        return [
            'products' => $products->values(),
            'total' => $total,
        ];
    }

    public function visits()
    {
        return $this->belongsToMany(Visit::class, 'visit_product')->withTimestamps();
    }
}
