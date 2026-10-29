<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Config;

class SmtpSettings
{
    public static function applyFromDatabase(): void
    {
        if (AppSetting::getValue('smtp_enabled', '0') !== '1') {
            return;
        }

        $host = (string) AppSetting::getValue('smtp_host', '');
        if ($host === '') {
            return;
        }

        $port = (int) AppSetting::getValue('smtp_port', 587);
        $encryption = (string) AppSetting::getValue('smtp_encryption', 'tls');
        $username = (string) AppSetting::getValue('smtp_username', '');
        $password = (string) AppSetting::getValue('smtp_password', '');
        $fromAddress = (string) AppSetting::getValue('smtp_from_address', '');
        $fromName = (string) AppSetting::getValue('smtp_from_name', config('app.name'));

        // Laravel 13 / Symfony Mailer: ssl => smtps, tls/none => null scheme (STARTTLS on 587)
        $scheme = $encryption === 'ssl' ? 'smtps' : null;

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.scheme', $scheme);
        Config::set('mail.mailers.smtp.url', null);
        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.username', $username !== '' ? $username : null);
        Config::set('mail.mailers.smtp.password', $password !== '' ? $password : null);
        Config::set('mail.mailers.smtp.timeout', 30);

        if ($fromAddress !== '') {
            Config::set('mail.from.address', $fromAddress);
            Config::set('mail.from.name', $fromName !== '' ? $fromName : config('app.name'));
        }
    }
}
