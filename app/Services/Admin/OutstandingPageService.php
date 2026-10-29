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
            'citiesByCompany' => $this->citiesByCompany(),
        ];
    }

    /**
     * Customer towns per company, for the City dropdown that narrows a region.
     *
     * Apna Bazar has no branch or city field of its own -- BusyPayload::city()
     * reads the town out of the party's address lines, so this is the only real
     * geography the CRM holds. Key '' carries every town, for when no region is
     * picked.
     *
     * @return array<string, list<string>>
     */
    private function citiesByCompany(): array
    {
        $rows = Party::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->get(['company', 'city']);

        $grouped = ['' => []];

        foreach ($rows as $row) {
            $company = (string) ($row->company ?? '');
            $city = (string) $row->city;

            $grouped[$company][] = $city;
            $grouped[''][] = $city;
        }

        return array_map(
            fn (array $cities) => array_values(array_unique($cities)),
            $grouped,
        );
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
            $query->salesSeriesOnly();
        }

        return $query;
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
        // Region is the company whose books the row came from, answered by the
        // column the sync stamps. It used to be guessed from the customer's own
        // address, which filed Mumbai-company ledgers under Pune and Gujarat
        // whenever the customer happened to be based there.
        $company = $filters['company'] ?? null;

        // Older links and the mobile API send the region as `city`. Honour that
        // when it names a company and no explicit region was given.
        if (empty($company) && ! empty($filters['city']) && BusyCompanies::exists($filters['city'])) {
            $company = $filters['city'];
            $filters['city'] = null;
        }

        if (! empty($company) && BusyCompanies::exists($company)) {
            $query->where('company', BusyCompanies::resolve($company));
        }

        // City is the customer's own town, derived from their address by
        // BusyPayload::city(). It narrows within a region rather than replacing it.
        if (! empty($filters['city'])) {
            $city = $filters['city'];
            $query->whereHas('party', fn ($q) => $q->where('city', $city)->orWhere('state', $city));
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
