<?php

namespace App\Services\Admin;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Schema;

class ProductPageService
{
    public const PER_PAGE_OPTIONS = [25, 50, 100, 200];

    public function data(?string $search = null, string|int|null $perPageInput = 50): array
    {
        $search = trim((string) $search);
        [$perPage, $perPageKey] = $this->resolvePerPage($perPageInput);

        if (! Schema::hasTable('products')) {
            return [
                'products' => new Paginator([], 0, $perPage, 1, [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]),
                'search' => $search,
                'perPage' => $perPageKey,
                'perPageOptions' => self::PER_PAGE_OPTIONS,
            ];
        }

        $query = Product::query()->orderBy('name');

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('product_code', 'like', $like)
                    ->orWhere('category', 'like', $like)
                    ->orWhere('hsn_code', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('size_sqmm', 'like', $like)
                    ->orWhere('core', 'like', $like)
                    ->orWhere('spec', 'like', $like)
                    ->orWhere('colour', 'like', $like)
                    ->orWhere('unit', 'like', $like);
            });
        }

        /** @var LengthAwarePaginator $products */
        $products = $query->paginate($perPage)->withQueryString();

        return [
            'products' => $products,
            'search' => $search,
            'perPage' => $perPageKey,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ];
    }

    /** @return array{0: int, 1: int|string} */
    private function resolvePerPage(string|int|null $perPageInput): array
    {
        if (is_string($perPageInput) && strtolower($perPageInput) === 'all') {
            return [1000, 'all'];
        }

        $perPage = (int) $perPageInput;
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = 50;
        }

        return [$perPage, $perPage];
    }
}
