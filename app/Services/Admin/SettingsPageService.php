<?php

namespace App\Services\Admin;

use App\Models\AppSetting;

class SettingsPageService
{
    public function data(): array
    {
        return [
            'companyName' => AppSetting::getValue('company_name', 'Tegh Cables India Pvt. Ltd.'),
            'gstin' => AppSetting::getValue('gstin', '27AACCT1234F1Z5'),
            'office' => AppSetting::getValue('registered_office', 'MIDC Industrial Area, Bhiwandi, Maharashtra'),
            'reminder' => AppSetting::getValue('followup_reminder', '1 day before due date'),
            'lastSync' => AppSetting::getValue('busy_last_sync', 'Never'),
            'busyStatus' => AppSetting::getValue('busy_status', 'Not connected'),
            'busyUsername' => AppSetting::getValue('busy_username', config('busy.username')),
            'busyBaseUrl' => AppSetting::getValue('busy_base_url', config('busy.base_url')),
            'hasBusyPassword' => filled(AppSetting::getValue('busy_password') ?: config('busy.password')),
            'smtpEnabled' => AppSetting::getValue('smtp_enabled', '0') === '1',
            'smtpHost' => AppSetting::getValue('smtp_host', ''),
            'smtpPort' => AppSetting::getValue('smtp_port', '587'),
            'smtpEncryption' => AppSetting::getValue('smtp_encryption', 'tls'),
            'smtpUsername' => AppSetting::getValue('smtp_username', ''),
            'smtpPassword' => AppSetting::getValue('smtp_password', ''),
            'smtpFromAddress' => AppSetting::getValue('smtp_from_address', ''),
            'smtpFromName' => AppSetting::getValue('smtp_from_name', 'Tegh Cables CRM'),
        ];
    }
}
