<?php

namespace App\Services\Admin;

use App\Models\Invoice;
use App\Models\Party;
use App\Models\User;
use App\Support\BusyCompanies;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class OutstandingPageService
{
    /**
     * Kept for anything still referencing the constant. The dropdown is built from
     * the configured companies instead, so adding a fourth company needs no change
     * here.
     *
     * @deprecated Use BusyCompanies::all() labels.
     */
    public const CITIES = ['Pune', 'Mumbai', 'Gujarat'];

    public function data(array $filters): array
    {
        $query = $this->applyFilters($this->baseQuery()->orderByDesc('outstanding'), $filters);

        /** @var LengthAwarePaginator $invoices */
        $invoices = $query->paginate(50)->withQueryString();

        return [
            'invoices' => $invoices,
            'filters' => $filters,
            'totalCount' => $this->baseQuery()->count(),
            'filteredCount' => $invoices->total(),
            'parties' => Party::orderBy('name')->get(['id', 'name']),
            'assignees' => User::where('role', 'telecaller')->orderBy('name')->get(),
            'cities' => array_column(BusyCompanies::all(), 'label'),
        ];
    }

    public function exportQuery(array $filters): Builder
    {
        return $this->applyFilters(
            $this->baseQuery()->orderBy('invoice_no'),
            $filters
        );
    }

    private function baseQuery(): Builder
    {
        $query = Invoice::with(['party', 'assignee', 'activePromise']);

        if (auth()->user()?->isAdmin()) {
            $this->limitToSalesInvoices($query);
        }

        return $query;
    }

    /**
     * Admins see sales invoices only, not the journal (JV-) and payment (PV-)
     * vouchers that also arrive in the outstanding feed.
     *
     * Each company numbers its bills differently -- Mumbai TCIPL/, Gujarat TCIG/,
     * Pune TCIPLP -- so the prefix is matched per company. The previous single
     * 'TCIPL%' hid all 319 Gujarat invoices and matched Pune's only because
     * TCIPLP happens to begin with TCIPL.
     */
    private function limitToSalesInvoices(Builder $query): void
    {
        $companies = array_filter(
            BusyCompanies::all(),
            fn (array $company) => $company['invoice_prefixes'] !== [],
        );

        if ($companies === []) {
            return;
        }

        $query->where(function (Builder $outer) use ($companies) {
            foreach ($companies as $slug => $company) {
                $outer->orWhere(function (Builder $scoped) use ($slug, $company) {
                    $scoped->where('company', $slug)->where(function (Builder $prefixes) use ($company) {
                        foreach ($company['invoice_prefixes'] as $prefix) {
                            $prefixes->orWhere('invoice_no', 'like', $prefix.'%');
                        }
                    });
                });
            }
        });
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['party'])) {
            $like = '%'.$filters['party'].'%';
            $query->whereHas('party', fn ($q) => $q->where('name', 'like', $like));
        }
        if (! empty($filters['invoice'])) {
            $query->where('invoice_no', 'like', '%'.$filters['invoice'].'%');
        }
        if (! empty($filters['sales'])) {
            $query->where('sales_person', $filters['sales']);
        }
        if (! empty($filters['branch'])) {
            $query->where('branch', $filters['branch']);
        }
        if (! empty($filters['assigned'])) {
            $query->where('assigned_to', $filters['assigned']);
        }
        if (! empty($filters['city'])) {
            $city = $filters['city'];

            // The three region names are now companies, answered by the column the
            // sync stamps. They used to be guessed from the customer's own address,
            // which put Mumbai-company ledgers under Pune and Gujarat whenever the
            // customer happened to be based there.
            if (BusyCompanies::exists($city)) {
                $query->where('company', BusyCompanies::resolve($city));
            } else {
                $query->whereHas('party', fn ($q) => $q->where('city', $city)->orWhere('state', $city));
            }
        }
        if (! empty($filters['status'])) {
            $status = $filters['status'];
            $query->where(function ($q) use ($status) {
                match ($status) {
                    'Paid' => $q->where('outstanding', '<=', 0),
                    'Overdue' => $q->where('outstanding', '>', 0)->whereDate('due_date', '<', now()->toDateString()),
                    'Due Today' => $q->where('outstanding', '>', 0)->whereDate('due_date', now()->toDateString()),
                    'Promise Given' => $q->whereHas('activePromise'),
                    default => $q->where('status', $status),
                };
            });
        }
        if (! empty($filters['aging'])) {
            $query->where('outstanding', '>', 0);
            $bucket = $filters['aging'];
            match ($bucket) {
                '0-7 Days' => $query->whereDate('due_date', '>=', now()->subDays(7)->toDateString()),
                '8-15 Days' => $query->whereBetween('due_date', [now()->subDays(15)->toDateString(), now()->subDays(8)->toDateString()]),
                '16-30 Days' => $query->whereBetween('due_date', [now()->subDays(30)->toDateString(), now()->subDays(16)->toDateString()]),
                '31-60 Days' => $query->whereBetween('due_date', [now()->subDays(60)->toDateString(), now()->subDays(31)->toDateString()]),
                '61-90 Days' => $query->whereBetween('due_date', [now()->subDays(90)->toDateString(), now()->subDays(61)->toDateString()]),
                '90+ Days' => $query->whereDate('due_date', '<', now()->subDays(90)->toDateString()),
                default => $query,
            };
        }

        return $query;
    }
}
