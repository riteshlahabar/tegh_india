<?php

namespace App\Services\Admin;

use App\Models\Call;
use App\Models\User;
use Carbon\Carbon;

class CallHistoryPageService
{
    public function data(array $filters): array
    {
        $user = auth()->user();

        $query = Call::with(['party', 'invoice', 'telecaller'])
            ->whereHas('party')
            ->latest('called_at');

        // A telecaller's history is their own. Unscoped before, so every user
        // saw the whole team's calls -- and the app's History tab is
        // explicitly "your call log".
        if ($user && ! $user->isAdmin()) {
            $query->where('telecaller_id', $user->id);
        }

        if (! empty($filters['date'])) {
            $query->whereDate('called_at', $filters['date']);
        }

        // Coarse ranges for the app's Today / This week tabs. `date` stays for
        // the web page's exact-day picker.
        $range = $filters['range'] ?? null;
        if ($range === 'today') {
            $query->whereDate('called_at', Carbon::today());
        } elseif ($range === 'week') {
            $query->where('called_at', '>=', Carbon::today()->startOfWeek());
        }
        if (! empty($filters['telecaller'])) {
            $query->where('telecaller_id', $filters['telecaller']);
        }
        if (! empty($filters['outcome'])) {
            $query->where('outcome', $filters['outcome']);
        }
        if (! empty($filters['party'])) {
            $like = '%'.$filters['party'].'%';
            $query->whereHas('party', fn ($q) => $q->where('name', 'like', $like));
        }

        return [
            'calls' => $query->paginate(50)->withQueryString(),
            'filters' => $filters,
            'telecallers' => User::where('role', 'telecaller')->orderBy('name')->get(),
        ];
    }
}
