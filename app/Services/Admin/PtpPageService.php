<?php

namespace App\Services\Admin;

use App\Models\PaymentPromise;

class PtpPageService
{
    public function data(): array
    {
        $promises = PaymentPromise::with(['party', 'invoice.assignee', 'telecaller', 'invoice.activePromise'])
            ->whereHas('invoice')
            ->whereHas('party')
            ->whereNull('collected_at')
            ->orderBy('promise_date')
            ->get();

        $today = now()->toDateString();

        return [
            'promises' => $promises,
            'ptpTotal' => $promises->sum('amount'),
            'ptpDueToday' => $promises->filter(fn ($p) => $p->promise_date->toDateString() === $today)->count(),
            'ptpOverdue' => $promises->filter(fn ($p) => $p->promise_date->toDateString() < $today)->count(),
            'ptpCollected' => PaymentPromise::whereNotNull('collected_at')->sum('amount'),
        ];
    }
}
