<?php

namespace App\Services\Admin;

use App\Models\AppSetting;
use App\Models\WhatsappMessage;
use App\Services\WhatsApp\GreenApiClient;

class WhatsappPageService
{
    public function data(): array
    {
        $client = app(GreenApiClient::class);

        return [
            'messages' => WhatsappMessage::with(['party', 'invoice', 'user'])
                ->latest('sent_at')
                ->paginate(50),
            'greenApiUrl' => AppSetting::getValue('greenapi_api_url', config('greenapi.api_url')),
            'greenMediaUrl' => AppSetting::getValue('greenapi_media_url', config('greenapi.media_url')),
            'greenInstanceId' => AppSetting::getValue('greenapi_id_instance', config('greenapi.id_instance')),
            'greenPhone' => $client->senderPhone(),
            'hasGreenToken' => $client->isConfigured(),
        ];
    }
}
