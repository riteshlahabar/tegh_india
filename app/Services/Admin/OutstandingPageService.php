<?php

namespace App\Services\Admin;

use App\Models\Invoice;
use App\Models\Party;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class OutstandingPageService
{
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
            'cities' => self::CITIES,
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
            $query->where('invoice_no', 'like', 'TCIPL%');
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
        if (! empty($filters['city'])) {
            $city = $filters['city'];
            $query->whereHas('party', function ($q) use ($city) {
                if ($city === 'Mumbai') {
                    $q->whereIn('city', ['Mumbai', 'Navi Mumbai', 'Thane', 'Palghar', 'Bhiwandi']);
                } elseif ($city === 'Gujarat') {
                    $q->where('state', 'Gujarat')
                        ->orWhere('city', 'Gujarat')
                        ->orWhereIn('city', ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Bhavnagar', 'Jamnagar', 'Gandhinagar', 'Ankleshwar', 'Bharuch', 'Vapi', 'Valsad', 'Dahej', 'Sanand', 'Halol', 'Nadiad', 'Morbi']);
                } elseif ($city === 'Pune') {
                    $q->where('city', 'Pune');
                } else {
                    $q->where('city', $city)->orWhere('state', $city);
                }
            });
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
