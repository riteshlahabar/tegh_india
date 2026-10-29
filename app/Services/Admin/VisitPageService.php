<?php

namespace App\Services\Admin;

use App\Models\Visit;

class VisitPageService
{
    public function data(): array
    {
        return [
            'visits' => Visit::with(['company', 'executive'])->orderBy('created_at', 'desc')->get(),
        ];
    }
}
