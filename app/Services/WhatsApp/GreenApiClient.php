<?php

namespace App\Services\WhatsApp;

use App\Models\AppSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GreenApiClient
{
    public function state(): string
    {
        $payload = $this->call('getStateInstance');

        return (string) ($payload['stateInstance'] ?? '');
    }

    public function sendText(string $phone, string $message): string
    {
        $state = $this->state();
        if ($state !== 'authorized') {
            throw new GreenApiException($this->stateMessage($state));
        }

        $payload = $this->call('sendMessage', [
            'chatId' => $this->chatId($phone),
            'message' => $message,
        ]);

        $id = (string) ($payload['idMessage'] ?? '');
        if ($id === '') {
            throw new GreenApiException('WhatsApp did not accept the message.');
        }

        return $id;
    }

    public function chatId(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) === 10) {
            $digits = '91'.$digits;
        }
        if ($digits === '') {
            throw new GreenApiException('This party has no mobile number.');
        }

        return $digits.'@c.us';
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     */
    public function call(string $method, ?array $body = null): array
    {
        $url = $this->endpoint($method);
        $pending = Http::timeout(20)->acceptJson();

        try {
            $response = $body === null
                ? $pending->get($url)
                : $pending->asJson()->post($url, $body);
        } catch (\Throwable $e) {
            throw new GreenApiException('WhatsApp request failed.', 0, $e);
        }

        return $this->decode($response);
    }

    public function endpoint(string $method): string
    {
        $base = rtrim($this->setting('greenapi_api_url', 'api_url'), '/');
        $id = $this->setting('greenapi_id_instance', 'id_instance');
        $token = $this->setting('greenapi_api_token', 'api_token');

        if ($base === '' || $id === '' || $token === '') {
            throw new GreenApiException('WhatsApp is not configured.');
        }

        return $base.'/waInstance'.$id.'/'.$method.'/'.$token;
    }

    public function senderPhone(): string
    {
        return $this->setting('greenapi_phone', 'phone');
    }

    public function isConfigured(): bool
    {
        return $this->setting('greenapi_api_url', 'api_url') !== ''
            && $this->setting('greenapi_id_instance', 'id_instance') !== ''
            && $this->setting('greenapi_api_token', 'api_token') !== '';
    }

    private function setting(string $key, string $configKey): string
    {
        $stored = AppSetting::getValue($key);

        return (string) ($stored !== null && $stored !== '' ? $stored : config('greenapi.'.$configKey, ''));
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        $json = $response->json();
        $payload = is_array($json) ? $json : [];

        if ($response->status() === 401) {
            throw new GreenApiException('WhatsApp token was rejected.');
        }
        if ($response->status() === 403) {
            throw new GreenApiException('WhatsApp instance or host is wrong.');
        }
        if ($response->failed()) {
            throw new GreenApiException('WhatsApp is not ready to send.');
        }

        return $payload;
    }

    private function stateMessage(string $state): string
    {
        return match ($state) {
            'notAuthorized' => 'WhatsApp is not authorized. Scan the QR code in the GREEN-API console.',
            'starting' => 'WhatsApp is still syncing. Try again in a moment.',
            'sleepMode' => 'The WhatsApp phone is offline.',
            'blocked', 'suspended' => 'This WhatsApp account is restricted.',
            default => 'WhatsApp is not authorized.',
        };
    }
}
