<?php

namespace App\Services\Admin;

use App\Models\Call;
use App\Models\Invoice;
use App\Models\PaymentPromise;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class DashboardPageService
{
    public function data(): array
    {
        if (! Schema::hasTable('invoices')) {
            return $this->empty();
        }

        $today = Carbon::today();
        $open = $this->openInvoices()->with(['party', 'assignee', 'activePromise']);

        $totalOutstanding = (clone $open)->sum('outstanding');
        $overdue = (clone $open)->whereDate('due_date', '<', $today);
        $dueToday = (clone $open)->whereDate('due_date', $today);

        // Promises are scoped through the invoice they sit on, so a scoped
        // telecaller does not see every company's PTP total. Unscoped before,
        // which made the figure meaningless on a phone.
        $user = auth()->user();
        $ptp = PaymentPromise::whereNull('collected_at')
            ->whereHas('invoice', fn (Builder $q) => $q->visibleTo($user));

        $ptpAmount = (clone $ptp)->sum('amount');
        $ptpCount = (clone $ptp)->count();

        $monthStart = $today->copy()->startOfMonth();
        $collectedThisMonth = (float) Invoice::where('outstanding', 0)
            ->whereMonth('updated_at', $today->month)
            ->whereYear('updated_at', $today->year)
            ->sum('received_amount');

        if ($collectedThisMonth <= 0) {
            $collectedThisMonth = (float) Invoice::sum('received_amount');
        }

        $followupsToday = $this->dialQueue()->count();

        // "Calls today" means this telecaller's own calls. It counted every
        // user's calls before, so each telecaller saw the whole team's number
        // as their own progress.
        $callsToday = Call::whereDate('called_at', $today)
            ->when(
                $user && ! $user->isAdmin(),
                fn (Builder $q) => $q->where('telecaller_id', $user->id),
            );

        $completedFollowups = (clone $callsToday)->count();

        // The two figures under the progress bar on the app's home card.
        $connectedToday = (clone $callsToday)
            ->whereIn('outcome', ['Connected', 'Payment Promise', 'Payment Confirmed'])
            ->count();

        $promisesToday = PaymentPromise::whereDate('created_at', $today)
            ->when(
                $user && ! $user->isAdmin(),
                fn (Builder $q) => $q->where('telecaller_id', $user->id),
            )
            ->count();

        $aging = array_fill_keys(Invoice::AGING_BUCKETS, 0.0);
        $this->openInvoices()->get()->each(function (Invoice $invoice) use (&$aging) {
            $aging[$invoice->aging_bucket] += $invoice->outstanding;
        });

        $collectionTarget = 6000000;

        // The app's "Priority calls" and the web dashboard's follow-up table.
        // This was `openInvoices()->orderByDesc('outstanding')->take(6)` --
        // the six largest open invoices, with no due or follow-up filter at
        // all -- so the list led with crore-sized "Not Due" rows nobody had
        // a reason to ring today.
        //
        // Today's dues only -- invoices whose due date *is* today. Overdue
        // bills are deliberately not here: they have their own tile and their
        // own filter on the Outstanding list, and mixing them in is what
        // buried today's actual work. Biggest first.
        //
        // Still inside the dial queue, so an invoice due today that someone
        // has already agreed to ring next week stays out of it.
        $todayFollowups = $this->dialQueue()
            ->with(['party', 'assignee', 'activePromise'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', $today)
            ->orderByDesc('outstanding')
            ->take(6)
            ->get();

        $teamSnapshot = User::where('role', 'telecaller')->orderBy('name')->get()->map(function (User $user) {
            $mine = Invoice::where('assigned_to', $user->id)->where('outstanding', '>', 0);

            return [
                'name' => $user->name,
                'initials' => $user->initials ?: crm_initials($user->name),
                'count' => (clone $mine)->count(),
                'amount' => (clone $mine)->sum('outstanding'),
            ];
        });

        return [
            'kpis' => [
                'totalOutstanding' => $totalOutstanding,
                'openInvoices' => $this->openInvoices()->count(),
                'overdue' => (clone $overdue)->sum('outstanding'),
                // The count was computed and then thrown away; the mobile home
                // screen needs it for the Overdue tile and the bell badge.
                'overdueCount' => (clone $overdue)->count(),
                'dueToday' => (clone $dueToday)->sum('outstanding'),
                'dueTodayCount' => (clone $dueToday)->count(),
                'ptpAmount' => $ptpAmount,
                'ptpCount' => $ptpCount,
                'collectedThisMonth' => $collectedThisMonth,
                'followupsToday' => $followupsToday,
                'completedFollowups' => $completedFollowups,
                'connectedToday' => $connectedToday,
                'promisesToday' => $promisesToday,
            ],
            'agingLabels' => array_keys($aging),
            'agingValues' => array_values($aging),
            'collectedAmount' => $collectedThisMonth,
            'collectionPending' => max($collectionTarget - $collectedThisMonth, 0),
            'todayFollowups' => $todayFollowups,
            'teamSnapshot' => $teamSnapshot,
        ];
    }

    private function openInvoices(): Builder
    {
        return Invoice::query()
            ->where('outstanding', '>', 0)
            ->visibleTo(auth()->user());
    }

    /**
     * Today's dial queue, as agreed for the mobile app: an open invoice that
     * has never been scheduled, or whose follow-up fell due today or earlier.
     *
     * The old count asked for `next_followup_on >= today`, which is the
     * opposite -- it counted every invoice scheduled for some future date
     * plus every unscheduled one, so "Follow-ups today" read as essentially
     * the whole book (2,136 on live) and the home card's progress bar had a
     * target nobody could ever reach.
     *
     * Both the count and the priority list come through here so they cannot
     * disagree about what "today's work" means.
     */
    private function dialQueue(): Builder
    {
        $today = Carbon::today();

        return $this->openInvoices()->where(function (Builder $query) use ($today) {
            $query->whereNull('next_followup_on')
                ->orWhereDate('next_followup_on', '<=', $today);
        });
    }

    private function empty(): array
    {
        return [
            'kpis' => [
                'totalOutstanding' => 0,
                'openInvoices' => 0,
                'overdue' => 0,
                'overdueCount' => 0,
                'dueToday' => 0,
                'dueTodayCount' => 0,
                'ptpAmount' => 0,
                'ptpCount' => 0,
                'collectedThisMonth' => 0,
                'followupsToday' => 0,
                'completedFollowups' => 0,
                'connectedToday' => 0,
                'promisesToday' => 0,
            ],
            'agingLabels' => Invoice::AGING_BUCKETS,
            'agingValues' => array_fill(0, 6, 0),
            'collectedAmount' => 0,
            'collectionPending' => 6000000,
            'todayFollowups' => collect(),
            'teamSnapshot' => collect(),
        ];
    }
}
