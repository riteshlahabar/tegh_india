<?php

namespace App\Services\Admin;

use App\Models\ApnaAccount;
use App\Models\ApnaGroup;
use App\Models\ApnaItem;
use App\Models\ApnaOutstanding;
use App\Models\ApnaPendingOrder;
use App\Models\ApnaSyncLog;
use App\Models\ApnaVoucher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Schema;

class ApnaPageService
{
    public const TABS = [
        'accounts' => 'Accounts',
        'items' => 'Items',
        'outstanding' => 'Outstanding',
        'vouchers' => 'Vouchers',
        'orders' => 'Pending orders',
        'groups' => 'Groups',
    ];

    public function data(Request $request): array
    {
        $tab = (string) $request->query('tab', 'accounts');
        if (! array_key_exists($tab, self::TABS)) {
            $tab = 'accounts';
        }

        $search = trim((string) $request->query('q', ''));
        $empty = new Paginator([], 0, 50, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        if (! Schema::hasTable('apna_accounts')) {
            return [
                'tab' => $tab,
                'tabs' => self::TABS,
                'search' => $search,
                'rows' => $empty,
                'counts' => array_fill_keys(array_keys(self::TABS), 0),
                'lastSync' => null,
                'endpoints' => $this->endpoints(),
            ];
        }

        return [
            'tab' => $tab,
            'tabs' => self::TABS,
            'search' => $search,
            'rows' => $this->rows($tab, $search),
            'counts' => [
                'accounts' => ApnaAccount::query()->count(),
                'items' => ApnaItem::query()->count(),
                'outstanding' => ApnaOutstanding::query()->count(),
                'vouchers' => ApnaVoucher::query()->count(),
                'orders' => ApnaPendingOrder::query()->count(),
                'groups' => ApnaGroup::query()->count(),
            ],
            'lastSync' => ApnaSyncLog::query()->latest('id')->first(),
            'endpoints' => $this->endpoints(),
        ];
    }

    private function rows(string $tab, string $search): LengthAwarePaginator
    {
        $query = match ($tab) {
            'items' => $this->search(ApnaItem::query()->orderBy('name'), $search, ['name', 'alias', 'parent_group', 'hsn_code', 'unit']),
            'outstanding' => $this->search(ApnaOutstanding::query()->orderByDesc('invoice_date'), $search, ['account_name', 'invoice_no']),
            'vouchers' => $this->search(ApnaVoucher::query()->orderByDesc('tran_date'), $search, ['voucher_no', 'narration', 'kind', 'status']),
            'orders' => $this->search(ApnaPendingOrder::query()->orderByDesc('order_date'), $search, ['item', 'account_name', 'order_ref']),
            'groups' => $this->search(ApnaGroup::query()->orderBy('kind')->orderBy('name'), $search, ['name', 'parent_name', 'kind']),
            default => $this->search(ApnaAccount::query()->orderBy('name'), $search, ['name', 'alias', 'mobile', 'gst_number', 'parent_group', 'state', 'email']),
        };

        return $query->paginate(50)->withQueryString();
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  list<string>  $columns
     */
    private function search(Builder $query, string $search, array $columns): Builder
    {
        if ($search === '') {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';

        return $query->where(function (Builder $inner) use ($columns, $like) {
            foreach ($columns as $column) {
                $inner->orWhere($column, 'like', $like);
            }
        });
    }

    /**
     * Read endpoints from the Apna Bazar collection that this CRM pulls.
     *
     * @return list<array{name: string, path: string}>
     */
    private function endpoints(): array
    {
        return [
            ['name' => 'Account masters', 'path' => 'get_accounts.php'],
            ['name' => 'Item masters', 'path' => 'get_items.php'],
            ['name' => 'Account groups', 'path' => 'get_account_group.php'],
            ['name' => 'Item groups', 'path' => 'get_item_group.php'],
            ['name' => 'Outstanding', 'path' => 'get_outstanding.php'],
            ['name' => 'Sales vouchers', 'path' => 'get_inventoryvch_list.php'],
            ['name' => 'Receipt vouchers', 'path' => 'get_accountingvch_list.php'],
            ['name' => 'Pending orders', 'path' => 'get_busypendingorder.php'],
        ];
    }
}
