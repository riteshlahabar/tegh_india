<?php

namespace App\Services\Admin;

use App\Models\Invoice;
use Carbon\Carbon;

class FollowupPageService
{
    public function data(?string $search = null): array
    {
        $today = Carbon::today();
        $query = Invoice::with(['party', 'assignee', 'activePromise'])
            ->where('outstanding', '>', 0)
            ->where(function ($q) use ($today) {
                $q->whereNull('next_followup_on')
                    ->orWhereDate('next_followup_on', '>=', $today);
            });

        $search = trim((string) $search);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('invoice_no', 'like', $like)
                    ->orWhereHas('party', fn ($p) => $p->where('name', 'like', $like));
            });
        }

        $invoices = $query->get()->sortBy(function (Invoice $invoice) {
            return match ($invoice->priority) {
                'high' => 0,
                'med' => 1,
                default => 2,
            };
        })->values();

        return [
            'invoices' => $invoices,
            'search' => $search,
            'todayLabel' => $today->format('d M Y'),
        ];
    }
}
