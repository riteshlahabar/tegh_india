<?php

namespace App\Services\Admin;

use App\Models\Call;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\PaymentPromise;
use App\Models\User;
use Illuminate\Support\Collection;

class ReportPageService
{
    public function data(array $filters): array
    {
        $type = $filters['type'] ?? null;
        $invoices = $this->filteredInvoices($filters);

        $reports = [
            ['key' => 'outstanding', 'name' => 'Outstanding Report', 'icon' => 'fa-file-invoice-dollar', 'desc' => 'Complete list of open invoices with party-wise outstanding.'],
            ['key' => 'aging', 'name' => 'Aging Report', 'icon' => 'fa-hourglass-half', 'desc' => 'Outstanding value grouped by 0-7 / 8-15 / 16-30 / 31-60 / 61-90 / 90+ day buckets.'],
            ['key' => 'telecaller', 'name' => 'Telecaller Performance', 'icon' => 'fa-headset', 'desc' => 'Calls made, connected, PTP created and amount collected by telecaller.'],
            ['key' => 'collection', 'name' => 'Collection Report', 'icon' => 'fa-sack-dollar', 'desc' => 'Month-wise collected amount against target.'],
            ['key' => 'ptp', 'name' => 'Payment Promise Report', 'icon' => 'fa-handshake', 'desc' => 'All active, upcoming and broken payment promises.'],
            ['key' => 'overdue', 'name' => 'Overdue Report', 'icon' => 'fa-triangle-exclamation', 'desc' => 'Invoices past due date with days overdue and assigned telecaller.'],
            ['key' => 'party', 'name' => 'Party-wise Outstanding', 'icon' => 'fa-building', 'desc' => 'Outstanding rolled up by customer across all invoices.'],
            ['key' => 'salesperson', 'name' => 'Salesperson-wise Outstanding', 'icon' => 'fa-user-tie', 'desc' => 'Outstanding rolled up by the original sales owner.'],
        ];

        return [
            'reports' => $reports,
            'filters' => $filters,
            'type' => $type,
            'resultTitle' => collect($reports)->firstWhere('key', $type)['name'] ?? null,
            'resultRows' => $type ? $this->buildResult($type, $invoices, $filters) : collect(),
            'parties' => Party::orderBy('name')->get(['id', 'name']),
            'telecallers' => User::where('role', 'telecaller')->orderBy('name')->get(),
        ];
    }

    public function filteredInvoices(array $filters): Collection
    {
        $query = Invoice::with(['party', 'assignee', 'activePromise']);

        if (! empty($filters['from'])) {
            $query->whereDate('invoice_date', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('invoice_date', '<=', $filters['to']);
        }
        if (! empty($filters['party_id'])) {
            $query->where('party_id', $filters['party_id']);
        }
        if (! empty($filters['sales'])) {
            $query->where('sales_person', $filters['sales']);
        }
        if (! empty($filters['telecaller'])) {
            $query->where('assigned_to', $filters['telecaller']);
        }
        if (! empty($filters['branch'])) {
            $query->where('branch', $filters['branch']);
        }
        if (! empty($filters['status'])) {
            if ($filters['status'] === 'Paid') {
                $query->where('outstanding', '<=', 0);
            } elseif ($filters['status'] === 'Overdue') {
                $query->where('outstanding', '>', 0)->whereDate('due_date', '<', now()->toDateString());
            }
        }

        return $query->get();
    }

    private function buildResult(string $type, Collection $invoices, array $filters): Collection
    {
        return match ($type) {
            'aging' => collect(Invoice::AGING_BUCKETS)->map(fn ($bucket) => [
                'label' => $bucket,
                'count' => $invoices->where('outstanding', '>', 0)->filter(fn ($i) => $i->aging_bucket === $bucket)->count(),
                'amount' => $invoices->where('outstanding', '>', 0)->filter(fn ($i) => $i->aging_bucket === $bucket)->sum('outstanding'),
            ]),
            'telecaller' => User::where('role', 'telecaller')->orderBy('name')->get()->map(function (User $user) {
                return [
                    'label' => $user->name,
                    'calls' => Call::where('telecaller_id', $user->id)->count(),
                    'connected' => Call::where('telecaller_id', $user->id)->where('outcome', 'Connected')->count(),
                    'ptp' => PaymentPromise::where('telecaller_id', $user->id)->count(),
                    'amount' => Invoice::where('assigned_to', $user->id)->sum('outstanding'),
                ];
            }),
            'collection' => collect([[
                'label' => now()->format('M Y'),
                'collected' => Invoice::sum('received_amount'),
                'target' => 6000000,
            ]]),
            'ptp' => PaymentPromise::with(['party', 'invoice'])->orderByDesc('promise_date')->get()->map(fn ($p) => [
                'label' => $p->party?->name,
                'invoice' => $p->invoice?->invoice_no,
                'amount' => $p->amount,
                'date' => crm_date($p->promise_date),
                'status' => $p->display_status,
            ]),
            'overdue' => $invoices->filter(fn ($i) => $i->computed_status === 'Overdue')->values(),
            'party' => $invoices->groupBy('party_id')->map(fn ($group) => [
                'label' => $group->first()->party?->name,
                'count' => $group->count(),
                'amount' => $group->sum('outstanding'),
            ])->values(),
            'salesperson' => $invoices->groupBy('sales_person')->map(fn ($group, $name) => [
                'label' => $name ?: 'Unassigned',
                'count' => $group->count(),
                'amount' => $group->sum('outstanding'),
            ])->values(),
            default => $invoices->values(),
        };
    }
}
