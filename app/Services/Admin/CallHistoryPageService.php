<?php

namespace App\Services\Admin;

use App\Models\Call;
use App\Models\User;

class CallHistoryPageService
{
    public function data(array $filters): array
    {
        $query = Call::with(['party', 'invoice', 'telecaller'])->whereHas('party')->latest('called_at');

        if (! empty($filters['date'])) {
            $query->whereDate('called_at', $filters['date']);
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
