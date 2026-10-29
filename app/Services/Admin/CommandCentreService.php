<?php

namespace App\Services\Admin;

use App\Models\Call;
use App\Models\City;
use App\Models\Company;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;

class CommandCentreService
{
    public function data(): array
    {
        return array_merge($this->kpis(), $this->citySales(), $this->leaderboard(), $this->activityFeed());
    }

    private function kpis(): array
    {
        return [
            'todayEnquiries' => Company::whereDate('created_at', Carbon::today())->count(),
            'quotationsSent' => Quotation::count(),
            'ordersWon' => Quotation::where('status', 'Approved')->count(),
            'criticalFollowups' => Call::whereDate('next_followup', '>=', Carbon::today())->count(),
        ];
    }

    private function citySales(): array
    {
        $cities = City::main()->get();

        $citySales = $cities->map(function ($city) {
            $sales = Quotation::whereHas('company', function ($q) use ($city) {
                $q->where('city', $city->name);
            })->where('status', 'Approved')->sum('amount');

            return [
                'name' => $city->name,
                'sales' => $sales,
                'target' => 500000,
            ];
        });

        return compact('citySales');
    }

    private function leaderboard(): array
    {
        $executivesList = User::where('role', 'sales_executive')->get()->map(function ($exec) {
            $dealsCount = Company::where('assigned_exec_id', $exec->id)->count();
            $revenue = Quotation::whereHas('company', function ($q) use ($exec) {
                $q->where('assigned_exec_id', $exec->id);
            })->where('status', 'Approved')->sum('amount');

            return [
                'name' => $exec->name,
                'deals' => $dealsCount,
                'revenue' => $revenue,
            ];
        })->sortByDesc('revenue')->values();

        return compact('executivesList');
    }

    private function activityFeed(): array
    {
        $visitsFeed = Visit::with(['company', 'executive'])->orderBy('created_at', 'desc')->take(10)->get()->map(function ($visit) {
            return [
                'time' => $visit->checked_in_at->format('h:i A'),
                'user' => $visit->executive->name,
                'action' => 'Field Visit',
                'detail' => 'Check-in at '.$visit->company->name.($visit->status == 'checked_out' ? ' (Completed)' : ' (In-Progress)'),
                'raw_time' => $visit->checked_in_at,
            ];
        });

        $callsFeed = Call::with(['company', 'telecaller'])->orderBy('created_at', 'desc')->take(10)->get()->map(function ($call) {
            return [
                'time' => $call->called_at->format('h:i A'),
                'user' => $call->telecaller ? $call->telecaller->name : 'Admin',
                'action' => 'Telecall',
                'detail' => 'Called '.$call->company->name.' — Status: '.$call->outcome,
                'raw_time' => $call->called_at,
            ];
        });

        $quotesFeed = Quotation::with(['company'])->orderBy('created_at', 'desc')->take(10)->get()->map(function ($quote) {
            return [
                'time' => $quote->date->format('h:i A'),
                'user' => 'System',
                'action' => 'Quote Gen',
                'detail' => 'Quote '.$quote->quotation_number.' pushed to '.$quote->company->name,
                'raw_time' => $quote->date,
            ];
        });

        $activityLog = $visitsFeed->concat($callsFeed)->concat($quotesFeed)->sortByDesc('raw_time')->take(15)->values();

        return compact('activityLog');
    }
}
