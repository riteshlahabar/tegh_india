<?php

namespace App\Support;

use App\Models\AppSetting;

/**
 * The Apna Bazar companies the CRM pulls from.
 *
 * One Apna Bazar login == one company's books. The API takes no company code --
 * every endpoint accepts only Username and Password -- so the only way to reach
 * Gujarat or Pune is to authenticate as them. The CRM previously stored a single
 * credential pair, which is why only Mumbai data ever arrived.
 *
 * Credentials live in app_settings under busy_{slug}_username / busy_{slug}_password
 * so an admin can change them without a deploy, falling back to config/busy.php.
 * Mumbai additionally falls back to the legacy busy_username / busy_password keys,
 * which is where its credentials sit today.
 */
class BusyCompanies
{
    public const MUMBAI = 'mumbai';

    public const GUJARAT = 'gujarat';

    public const PUNE = 'pune';

    /**
     * Legacy single-credential keys, which hold Mumbai's login.
     */
    private const LEGACY_USERNAME_KEY = 'busy_username';

    private const LEGACY_PASSWORD_KEY = 'busy_password';

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return array<string, array{label: string, username: string, password: string}>
     */
    public static function all(): array
    {
        $companies = [];

        foreach ((array) config('busy.companies', []) as $slug => $definition) {
            $slug = (string) $slug;

            $companies[$slug] = [
                'label' => (string) ($definition['label'] ?? ucfirst($slug)),
                'username' => self::username($slug),
                'password' => self::password($slug),
                'invoice_prefixes' => self::invoicePrefixes($slug),
            ];
        }

        return $companies;
    }

    /**
     * Companies that actually have a usable credential pair. Syncing should iterate
     * these rather than every configured slug, so a half-filled setup degrades to
     * "fetch what we can" instead of throwing.
     *
     * @return list<string>
     */
    public static function configured(): array
    {
        return array_values(array_keys(array_filter(
            self::all(),
            fn (array $company) => $company['username'] !== '' && $company['password'] !== '',
        )));
    }

    public static function default(): string
    {
        $default = (string) config('busy.default_company', self::MUMBAI);

        return self::exists($default) ? $default : (self::slugs()[0] ?? self::MUMBAI);
    }

    public static function exists(?string $slug): bool
    {
        return $slug !== null && array_key_exists(self::normalise($slug), (array) config('busy.companies', []));
    }

    /**
     * Falls back to the default company so a missing or unknown slug never silently
     * reads another company's books.
     */
    public static function resolve(?string $slug): string
    {
        $slug = self::normalise((string) $slug);

        return self::exists($slug) ? $slug : self::default();
    }

    public static function label(?string $slug): string
    {
        $slug = self::resolve($slug);

        return (string) config("busy.companies.{$slug}.label", ucfirst($slug));
    }

    public static function username(string $slug): string
    {
        $slug = self::normalise($slug);

        return self::setting(
            "busy_{$slug}_username",
            (string) config("busy.companies.{$slug}.username", ''),
            $slug === self::MUMBAI ? self::LEGACY_USERNAME_KEY : null,
        );
    }

    public static function password(string $slug): string
    {
        $slug = self::normalise($slug);

        return self::setting(
            "busy_{$slug}_password",
            (string) config("busy.companies.{$slug}.password", ''),
            $slug === self::MUMBAI ? self::LEGACY_PASSWORD_KEY : null,
        );
    }

    /**
     * Sales-invoice number prefixes for a company, used to tell real invoices apart
     * from journal and payment vouchers on the Outstanding page.
     *
     * Each company numbers its own bills: Mumbai TCIPL/, Gujarat TCIG/, Pune TCIPLP.
     * A single hardcoded 'TCIPL%' hid every Gujarat invoice while matching Pune's
     * only by accident, TCIPLP happening to start with TCIPL.
     *
     * @return list<string>
     */
    public static function invoicePrefixes(string $slug): array
    {
        $slug = self::normalise($slug);

        return array_values(array_filter(
            (array) config("busy.companies.{$slug}.invoice_prefixes", []),
            fn ($prefix) => is_string($prefix) && $prefix !== '',
        ));
    }

    public static function hasCredentials(string $slug): bool
    {
        return self::username($slug) !== '' && self::password($slug) !== '';
    }

    public static function setCredentials(string $slug, ?string $username, ?string $password): void
    {
        $slug = self::normalise($slug);

        if ($username !== null) {
            AppSetting::setValue("busy_{$slug}_username", $username);
        }

        // A blank password means "keep the stored one" -- the settings form never
        // echoes the current value back, so an empty field is not a clear request.
        if ($password !== null && $password !== '') {
            AppSetting::setValue("busy_{$slug}_password", $password);
        }
    }

    public static function baseUrl(): string
    {
        $url = (string) (AppSetting::getValue('busy_base_url') ?: config('busy.base_url', ''));

        return rtrim($url, '/');
    }

    /**
     * Setting first, then the per-company config value, then the legacy key for
     * Mumbai. Reading the legacy key last means an admin who saves Mumbai through
     * the new form takes precedence over the old stored pair.
     */
    private static function setting(string $key, string $configDefault, ?string $legacyKey): string
    {
        $value = (string) (AppSetting::getValue($key) ?: '');
        if ($value !== '') {
            return $value;
        }

        if ($configDefault !== '') {
            return $configDefault;
        }

        return $legacyKey === null ? '' : (string) (AppSetting::getValue($legacyKey) ?: '');
    }

    private static function normalise(string $slug): string
    {
        return strtolower(trim($slug));
    }
}
