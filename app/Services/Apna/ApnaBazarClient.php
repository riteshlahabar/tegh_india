<?php

namespace App\Services\Apna;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ApnaBazarClient
{
    public function __construct(
        private ?string $baseUrl = null,
        private ?string $username = null,
        private ?string $password = null,
    ) {
        $this->baseUrl = rtrim($baseUrl ?? (string) config('services.apna.base_url'), '/');
        $this->username = $username ?? (string) config('services.apna.username');
        $this->password = $password ?? (string) config('services.apna.password');
    }

    /** @return list<array<string, mixed>> */
    public function accounts(): array
    {
        return $this->rows('get_accounts.php');
    }

    /** @return list<array<string, mixed>> */
    public function items(): array
    {
        return $this->rows('get_items.php');
    }

    /** @return list<array<string, mixed>> */
    public function accountGroups(): array
    {
        return $this->rows('get_account_group.php');
    }

    /** @return list<array<string, mixed>> */
    public function itemGroups(): array
    {
        return $this->rows('get_item_group.php');
    }

    /** @return list<array<string, mixed>> */
    public function outstanding(): array
    {
        return $this->rows('get_outstanding.php');
    }

    /** @return list<array<string, mixed>> */
    public function inventoryVouchers(string $voucherType = '9'): array
    {
        return $this->rows('get_inventoryvch_list.php', [
            'vchtype' => $voucherType,
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function accountingVouchers(string $voucherType = '14'): array
    {
        return $this->rows('get_accountingvch_list.php', [
            'vchtype' => $voucherType,
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function pendingOrders(string $startDate, string $endDate): array
    {
        return $this->rows('get_busypendingorder.php', [
            'Start_date' => $startDate,
            'End_date' => $endDate,
        ]);
    }

    /**
     * @param  array<string, scalar|null>  $extra
     * @return list<array<string, mixed>>
     */
    public function rows(string $script, array $extra = []): array
    {
        return $this->call($script, $extra)['rows'];
    }

    /**
     * @param  array<string, scalar|null>  $extra
     * @return array{rows: list<array<string, mixed>>, message: string}
     */
    public function call(string $script, array $extra = []): array
    {
        if ($this->username === '' || $this->password === '') {
            throw new RuntimeException('Apna Bazar username and password are not configured.');
        }

        $response = Http::asForm()
            ->timeout(90)
            ->acceptJson()
            ->post($this->baseUrl.'/'.ltrim($script, '/'), array_merge([
                'Username' => $this->username,
                'Password' => $this->password,
            ], $extra));

        $json = json_decode($response->body(), true);
        if (! is_array($json)) {
            throw new RuntimeException("Apna {$script} returned a non-JSON response (HTTP {$response->status()}).");
        }

        $code = $json['statuscode'] ?? $json['Statuscode'] ?? null;
        if ($code !== null && (string) $code !== '200') {
            $message = $json['statusresponse'] ?? $json['message'] ?? 'Request failed';
            throw new RuntimeException("Apna {$script}: {$message}");
        }

        $rows = [];
        if (array_is_list($json)) {
            $rows = $json;
        } elseif (isset($json['result']) && is_array($json['result']) && array_is_list($json['result'])) {
            $rows = $json['result'];
        } elseif (isset($json['ledgerdetails']) && is_array($json['ledgerdetails']) && array_is_list($json['ledgerdetails'])) {
            $rows = $json['ledgerdetails'];
        }

        return [
            'rows' => $rows,
            'message' => (string) ($json['statusresponse'] ?? 'ok'),
        ];
    }
}
