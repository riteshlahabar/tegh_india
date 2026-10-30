<?php

namespace App\Services\Admin;

use App\Models\Invoice;
use App\Models\Party;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CustomerPageService
{
    /**
     * The web page's list. Every party, with every invoice eager-loaded so the
     * rollups can be computed in PHP.
     *
     * Fine for a desktop table; far too much for a phone, which is what
     * {@see paged()} exists for.
     */
    public function data(?string $search = null): array
    {
        $search = trim((string) $search);

        $query = Party::with(['invoices.activePromise', 'assignee'])
            ->visibleTo(auth()->user())
            ->orderBy('name');

        $this->applySearch($query, $search);

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

    /**
     * The mobile app's list.
     *
     * Two differences that matter on a phone: it paginates, and it computes
     * the per-party rollups with aggregate subqueries instead of eager-loading
     * every invoice. The old shape sent thousands of parties and their whole
     * invoice history in one response.
     */
    public function paged(?string $search = null, int $perPage = 30): LengthAwarePaginator
    {
        $search = trim((string) $search);
        $user = auth()->user();

        // Invoices are still eager-loaded, because CrmPayload::party() falls
        // back to rollupStatus() and callInvoice(), both of which re-query the
        // relation when it is not loaded -- two extra queries per party. The
        // pagination is what bounds the cost; the response carries only the
        // party's aggregates, never the invoices themselves.
        $query = Party::query()
            ->with([
                'assignee',
                'invoices' => fn (Builder $q) => $q->visibleTo($user)->with('activePromise'),
            ])
            ->visibleTo($user)
            ->orderBy('name');

        $this->applySearch($query, $search);

        $parties = $query->paginate($perPage)->withQueryString();

        $parties->getCollection()->transform(function (Party $party) {
            $party->setAttribute('total_outstanding', $party->invoices->sum('outstanding'));
            $party->setAttribute('invoice_count', $party->invoices->count());
            $party->setAttribute('rollup_status', $party->rollupStatus());

            return $party;
        });

        return $parties;
    }

    public function show(int $id): array
    {
        // visibleTo first, so a party outside this account's region answers
        // 404 rather than handing over another company's customer.
        $party = Party::query()
            ->visibleTo(auth()->user())
            ->with(['assignee'])
            ->findOrFail($id);

        $invoices = Invoice::query()
            ->where('party_id', $party->id)
            ->visibleTo(auth()->user())
            ->with(['activePromise', 'assignee'])
            ->orderByDesc('outstanding')
            ->get();

        return [
            'party' => $party,
            'invoices' => $invoices,
            'calls' => $party->calls()
                ->with('telecaller', 'invoice')
                ->latest('called_at')
                ->limit(50)
                ->get(),
        ];
    }

    /**
     * Name or ERP code. Code-only search was missing, which made the app's
     * "search by name or code" promise false for anyone typing a code.
     */
    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';
        $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhere('mobile', 'like', $like);
        });
    }
}
