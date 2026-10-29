<?php

namespace App\Services\Admin;

use App\Models\Party;

class CustomerPageService
{
    public function data(?string $search = null): array
    {
        $search = trim((string) $search);
        $query = Party::with(['invoices.activePromise', 'assignee'])->orderBy('name');

        if ($search !== '') {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $parties = $query->get()->map(function (Party $party) {
            $party->setAttribute('total_outstanding', $party->invoices->sum('outstanding'));
            $party->setAttribute('invoice_count', $party->invoices->count());
            $party->setAttribute('rollup_status', $party->rollupStatus());

            return $party;
        });

        return [
            'parties' => $parties,
            'search' => $search,
        ];
    }

    public function show(int $id): array
    {
        $party = Party::with([
            'invoices.activePromise',
            'invoices.assignee',
            'assignee',
            'calls.telecaller',
        ])->findOrFail($id);

        return [
            'party' => $party,
            'invoices' => $party->invoices,
            'calls' => $party->calls()->with('telecaller', 'invoice')->latest('called_at')->get(),
        ];
    }
}
