<?php

namespace App\Services\Admin;

use App\Models\AppSetting;
use App\Support\BusyCompanies;

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
            'busyBaseUrl' => BusyCompanies::baseUrl(),
            'busyCompanies' => $this->busyCompanies(),
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

    /**
     * One row per Apna Bazar company for the settings form. Passwords are never sent
     * back to the browser -- the form only reports whether one is stored.
     *
     * @return list<array{slug: string, label: string, username: string, hasPassword: bool}>
     */
    private function busyCompanies(): array
    {
        return array_map(fn (string $slug) => [
            'slug' => $slug,
            'label' => BusyCompanies::label($slug),
            'username' => BusyCompanies::username($slug),
            'hasPassword' => BusyCompanies::password($slug) !== '',
        ], BusyCompanies::slugs());
    }
}
