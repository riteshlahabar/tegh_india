<?php

namespace App\Services\Admin;

use App\Models\EmailMessage;

class EmailPageService
{
    public function data(): array
    {
        return [
            'messages' => EmailMessage::with(['party', 'invoice', 'user'])
                ->latest('sent_at')
                ->paginate(50),
        ];
    }
}
