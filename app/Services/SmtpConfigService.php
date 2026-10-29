<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Config;

class SmtpConfigService
{
    public const KEYS = [
        'smtp_enabled',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'smtp_from_address',
        'smtp_from_name',
    ];

    public function settings(): array
    {
        return [
            'enabled' => (string) AppSetting::getValue('smtp_enabled', '0') === '1',
            'host' => (string) AppSetting::getValue('smtp_host', config('mail.mailers.smtp.host', '')),
            'port' => (string) AppSetting::getValue('smtp_port', (string) config('mail.mailers.smtp.port', '587')),
            'username' => (string) AppSetting::getValue('smtp_username', config('mail.mailers.smtp.username', '')),
            'password' => (string) AppSetting::getValue('smtp_password', ''),
            'encryption' => (string) AppSetting::getValue('smtp_encryption', 'tls'),
            'from_address' => (string) AppSetting::getValue('smtp_from_address', config('mail.from.address', '')),
            'from_name' => (string) AppSetting::getValue('smtp_from_name', config('mail.from.name', 'Tegh Cables CRM')),
        ];
    }

    public function isReady(): bool
    {
        $s = $this->settings();

        return $s['enabled']
            && $s['host'] !== ''
            && $s['from_address'] !== '';
    }

    public function save(array $input): void
    {
        AppSetting::setValue('smtp_enabled', ! empty($input['smtp_enabled']));
        AppSetting::setValue('smtp_host', trim((string) ($input['smtp_host'] ?? '')));
        AppSetting::setValue('smtp_port', trim((string) ($input['smtp_port'] ?? '587')));
        AppSetting::setValue('smtp_username', trim((string) ($input['smtp_username'] ?? '')));

        $password = $input['smtp_password'] ?? null;
        if (is_string($password) && $password !== '') {
            AppSetting::setValue('smtp_password', $password);
        }

        $encryption = strtolower(trim((string) ($input['smtp_encryption'] ?? 'tls')));
        if (! in_array($encryption, ['tls', 'ssl', 'none'], true)) {
            $encryption = 'tls';
        }
        AppSetting::setValue('smtp_encryption', $encryption === 'none' ? '' : $encryption);
        AppSetting::setValue('smtp_from_address', trim((string) ($input['smtp_from_address'] ?? '')));
        AppSetting::setValue('smtp_from_name', trim((string) ($input['smtp_from_name'] ?? 'Tegh Cables CRM')));
    }

    /**
     * Apply admin SMTP settings to Laravel mail config for this request.
     */
    public function apply(): void
    {
        $s = $this->settings();
        if (! $s['enabled']) {
            return;
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $s['host']);
        Config::set('mail.mailers.smtp.port', (int) $s['port']);
        Config::set('mail.mailers.smtp.username', $s['username'] !== '' ? $s['username'] : null);
        Config::set('mail.mailers.smtp.password', $s['password'] !== '' ? $s['password'] : null);
        Config::set('mail.mailers.smtp.scheme', null);

        $encryption = $s['encryption'];
        if ($encryption === 'ssl') {
            Config::set('mail.mailers.smtp.scheme', 'smtps');
        } elseif ($encryption === 'tls') {
            Config::set('mail.mailers.smtp.encryption', 'tls');
        } else {
            Config::set('mail.mailers.smtp.encryption', null);
        }

        if ($s['from_address'] !== '') {
            Config::set('mail.from.address', $s['from_address']);
            Config::set('mail.from.name', $s['from_name'] !== '' ? $s['from_name'] : 'Tegh Cables CRM');
        }
    }
}
