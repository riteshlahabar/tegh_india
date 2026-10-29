<?php

namespace App\Services\Busy;

use App\Models\AppSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BusyApiClient
{
    public function call(string $action, array $params = []): array
    {
        $endpoint = BusyEndpoints::CATALOG[$action] ?? null;
        if ($endpoint === null) {
            throw new BusyApiException("Unknown BUSY API action [{$action}].");
        }

        $username = $this->username();
        $password = $this->password();
        if ($username === '' || $password === '') {
            throw new BusyApiException('BUSY API credentials are not configured. Set BUSY_API_USERNAME and BUSY_API_PASSWORD.');
        }

        $payload = $this->mergeParams($params, $username, $password);
        $url = $this->url($endpoint['path']);

        $pending = Http::timeout($this->timeout())
            ->acceptJson()
            ->withOptions(['verify' => (bool) config('busy.verify_ssl', false)]);

        $pending = $endpoint['mode'] === 'multipart'
            ? $pending->asMultipart()
            : $pending->asForm();

        try {
            $response = $pending->post($url, $payload);
        } catch (\Throwable $e) {
            throw new BusyApiException('BUSY API request failed: '.$e->getMessage(), 0, $e);
        }

        if ($response->failed()) {
            throw new BusyApiException(
                "BUSY API [{$action}] returned HTTP {$response->status()}.",
                $response->status()
            );
        }

        return $this->decode($response);
    }

    public function getAccounts(?string $alias = null): array
    {
        return $this->call('get_accounts', array_filter(['Alias' => $alias], fn ($v) => $v !== null && $v !== ''));
    }

    public function getAccountLedger(string $erpAlias, string $startDate, string $endDate, string $pdc = 'N'): array
    {
        return $this->call('get_account_ledger', [
            'ERP_Alias' => $erpAlias,
            'Start_date' => $startDate,
            'End_date' => $endDate,
            'Pdc' => $pdc,
        ]);
    }

    public function getOutstanding(): array
    {
        return $this->call('get_outstanding');
    }

    public function getAccountGroup(): array
    {
        return $this->call('get_account_group');
    }

    public function addAccount(array $params): array
    {
        return $this->call('add_accounts', $params);
    }

    public function getItems(): array
    {
        return $this->call('get_items');
    }

    public function getItemGroup(): array
    {
        return $this->call('get_item_group');
    }

    public function addItem(array $params): array
    {
        return $this->call('add_item', $params);
    }

    public function addAccountingVoucher(array $params): array
    {
        return $this->call('add_accounting_voucher', $params);
    }

    public function addInventoryVoucher(array $params): array
    {
        return $this->call('add_inventory_voucher', $params);
    }

    public function getInventoryVoucherList(string $vchType): array
    {
        return $this->call('get_inventory_voucher_list', ['vchtype' => $vchType]);
    }

    public function getAccountingVoucherList(string $vchType): array
    {
        return $this->call('get_accounting_voucher_list', ['vchtype' => $vchType]);
    }

    public function getBusyOrderAdjustmentDetails(string $orderNo, ?string $itemErpCode = null): array
    {
        return $this->call('get_busy_order_adjustment_details', array_filter([
            'OrderNo' => $orderNo,
            'ItemErpCode' => $itemErpCode,
        ]));
    }

    public function getBusyChallanAdjustmentDetails(string $orderNo, ?string $itemErpCode = null): array
    {
        return $this->call('get_busy_challan_adjustment_details', array_filter([
            'OrderNo' => $orderNo,
            'ItemErpCode' => $itemErpCode,
        ]));
    }

    public function getBusyAllOrderAdjustment(string $startDate, string $endDate, array $extra = []): array
    {
        return $this->call('get_busy_all_order_adjustment', array_merge([
            'Start_date' => $startDate,
            'End_date' => $endDate,
        ], $extra));
    }

    public function getBusyAllChallanAdjustment(string $startDate, string $endDate, array $extra = []): array
    {
        return $this->call('get_busy_all_challan_adjustment', array_merge([
            'Start_date' => $startDate,
            'End_date' => $endDate,
        ], $extra));
    }

    public function getItemStockSummary(string $alias): array
    {
        return $this->call('get_item_stock_summary', ['Alias' => $alias]);
    }

    public function getBusyPendingOrder(string $startDate, string $endDate): array
    {
        return $this->call('get_busy_pending_order', [
            'Start_date' => $startDate,
            'End_date' => $endDate,
        ]);
    }

    public function getListOfVouchers(string $vchType, string $startDate, string $endDate): array
    {
        return $this->call('get_list_of_vouchers', [
            'vchtype' => $vchType,
            'Start_date' => $startDate,
            'End_date' => $endDate,
        ]);
    }

    public function getListOfVouchersWithDetails(string $vchType, string $startDate, string $endDate): array
    {
        return $this->call('get_list_of_vouchers_with_details', [
            'vchtype' => $vchType,
            'Start_date' => $startDate,
            'End_date' => $endDate,
        ]);
    }

    public function getVoucherDetails(string $finYear, string $vchCode): array
    {
        return $this->call('get_voucher_details', [
            'finyr' => $finYear,
            'vchcode' => $vchCode,
        ]);
    }

    public function getSerialwiseStockSummary(): array
    {
        return $this->call('get_serialwise_stock_summary');
    }

    public function getBatchwiseStockSummary(): array
    {
        return $this->call('get_batchwise_stock_summary');
    }

    public function getParamwiseStockSummary(): array
    {
        return $this->call('get_paramwise_stock_summary');
    }

    public function getPartyWiseSale(string $startDate, string $endDate, string $actCode): array
    {
        return $this->call('get_party_wise_sale', [
            'sd' => $startDate,
            'ed' => $endDate,
            'ActCode' => $actCode,
        ]);
    }

    public function getPartyGroupWiseSale(string $startDate, string $endDate, string $actCode, string $grpCode): array
    {
        return $this->call('get_party_group_wise_sale', [
            'sd' => $startDate,
            'ed' => $endDate,
            'ActCode' => $actCode,
            'GrpCode' => $grpCode,
        ]);
    }

    public function getPartyGroupWiseSaleAlt(string $startDate, string $endDate, string $actCode, string $grpCode): array
    {
        return $this->call('get_party_group_wise_sale_alt', [
            'sd' => $startDate,
            'ed' => $endDate,
            'ActCode' => $actCode,
            'GrpCode' => $grpCode,
        ]);
    }

    public function getItemWiseSale(string $startDate, string $endDate, string $actCode): array
    {
        return $this->call('get_item_wise_sale', [
            'sd' => $startDate,
            'ed' => $endDate,
            'ActCode' => $actCode,
        ]);
    }

    public function getItemSaleAnalysis(string $startDate, string $endDate): array
    {
        return $this->call('get_item_sale_analysis', [
            'sd' => $startDate,
            'ed' => $endDate,
        ]);
    }

    public function getItemWisePricing(?string $priceDate = null): array
    {
        return $this->call('get_item_wise_pricing', array_filter(['PriceDate' => $priceDate]));
    }

    public function getCancelledVoucherList(string $vchType, string $startDate, string $endDate): array
    {
        return $this->call('get_cancelled_voucher_list', [
            'vchtype' => $vchType,
            'Start_date' => $startDate,
            'End_date' => $endDate,
        ]);
    }

    public function getPriceStructure(string $vchType, string $busyMastCode, string $recType = '1'): array
    {
        return $this->call('get_price_structure', [
            'VchType' => $vchType,
            'BusyMastCode' => $busyMastCode,
            'RecType' => $recType,
        ]);
    }

    public function username(): string
    {
        return (string) (AppSetting::getValue('busy_username') ?: config('busy.username', ''));
    }

    public function password(): string
    {
        return (string) (AppSetting::getValue('busy_password') ?: config('busy.password', ''));
    }

    public function baseUrl(): string
    {
        $url = (string) (AppSetting::getValue('busy_base_url') ?: config('busy.base_url'));

        return rtrim($url, '/');
    }

    private function timeout(): int
    {
        return (int) config('busy.timeout', 90);
    }

    private function url(string $path): string
    {
        return $this->baseUrl().'/'.ltrim($path, '/');
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function mergeParams(array $params, string $username, string $password): array
    {
        $payload = [
            'Username' => $username,
            'Password' => $password,
        ];

        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $payload[$key] = is_array($value) ? json_encode($value) : $value;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        $json = $response->json();
        if (is_array($json)) {
            return $json;
        }

        $body = trim($response->body());
        if ($body === '') {
            return [];
        }

        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        Log::warning('BUSY API returned a non-JSON body', ['snippet' => mb_substr($body, 0, 300)]);

        return ['raw' => $body];
    }
}
