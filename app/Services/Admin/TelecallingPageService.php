<?php

namespace App\Services\Admin;

use App\Models\Call;

class TelecallingPageService
{
    public function data(): array
    {
        return [
            'allCalls' => Call::with(['company', 'telecaller'])->orderBy('created_at', 'desc')->get(),
        ];
    }
}
