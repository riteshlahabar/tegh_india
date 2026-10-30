<?php

namespace App\Services\Admin;

use App\Models\Call;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\PaymentPromise;
use App\Models\User;
use App\Support\BusyCompanies;
use Carbon\Carbon;

class StaffPageService
{
    /**
     * The Region -> City cascade is owned by OutstandingPageService, which
     * builds it from the invoices that actually exist. Reusing it here means
     * the dropdown that ASSIGNS a scope offers exactly the towns the scoped
     * list will later match.
     */
    public function __construct(private readonly OutstandingPageService $outstanding) {}

    public function data(): array
    {
        $today = Carbon::today();
        $users = User::orderByRaw("role = 'admin' desc")->orderBy('name')->get();

        $team = $users->map(function (User $user) use ($today) {
            $assigned = Invoice::where('assigned_to', $user->id);
            $calls = Call::where('telecaller_id', $user->id);

            return [
                'user' => $user,
                'assigned' => (clone $assigned)->count(),
                'followups' => (clone $assigned)->where('outstanding', '>', 0)->count(),
                'calls' => (clone $calls)->count(),
                'callsToday' => (clone $calls)->whereDate('called_at', $today)->count(),
                'connected' => (clone $calls)->whereIn('outcome', ['Connected', 'Payment Promise', 'Payment Confirmed'])->count(),
                'ptp' => PaymentPromise::where('telecaller_id', $user->id)->count(),
                'collected' => Invoice::where('assigned_to', $user->id)->sum('received_amount'),
            ];
        });

        return [
            'team' => $team,
            'companies' => BusyCompanies::all(),
            'citiesByCompany' => $this->outstanding->citiesByCompany(),
            'parties' => Party::orderBy('name')->get(['id', 'name', 'assigned_to']),
            'chartLabels' => $team->filter(fn ($row) => $row['user']->role === 'telecaller')->pluck('user.name')->values(),
            'chartCalls' => $team->filter(fn ($row) => $row['user']->role === 'telecaller')->pluck('calls')->values(),
            'chartConnected' => $team->filter(fn ($row) => $row['user']->role === 'telecaller')->pluck('connected')->values(),
            'chartPtp' => $team->filter(fn ($row) => $row['user']->role === 'telecaller')->pluck('ptp')->values(),
        ];
    }
}
