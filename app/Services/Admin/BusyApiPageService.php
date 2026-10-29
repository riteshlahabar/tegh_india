<?php

namespace App\Services\Admin;

use App\Services\Busy\BusyEndpoints;
use App\Support\BusyCompanies;
use Carbon\Carbon;

class BusyApiPageService
{
    /**
     * @return array{reports: list<array{action: string, name: string, group: string, write: bool, fields: list<array{name: string, label: string, type: string, options: list<array{value: string, label: string}>}>}>, today: string, yearStart: string}
     */
    public function data(): array
    {
        $today = now('Asia/Kolkata');
        $listed = BusyEndpoints::listed();
        $reads = array_values(array_filter($listed, fn (array $endpoint) => ! $endpoint['write']));
        $writes = array_values(array_filter($listed, fn (array $endpoint) => $endpoint['write']));

        return [
            'reports' => array_map($this->present(...), array_merge($reads, $writes)),
            'today' => $today->toDateString(),
            'yearStart' => $this->financialYearStart($today)->toDateString(),
            // The login decides whose books answer, so the page needs to say which
            // company it is asking. Unconfigured ones are listed but disabled.
            'companies' => array_map(fn (string $slug) => [
                'slug' => $slug,
                'label' => BusyCompanies::label($slug),
                'configured' => BusyCompanies::hasCredentials($slug),
            ], BusyCompanies::slugs()),
            'defaultCompany' => BusyCompanies::default(),
        ];
    }

    /**
     * @param  array{action: string, name: string, group: string, path: string, write: bool, params: list<string>}  $endpoint
     * @return array{action: string, name: string, group: string, write: bool, fields: list<array{name: string, label: string, type: string, options: list<array{value: string, label: string}>}>}
     */
    private function present(array $endpoint): array
    {
        $fields = [];
        foreach ($endpoint['params'] as $name) {
            $fields[] = [
                'name' => $name,
                'label' => $this->label($name),
                'type' => $this->type($name),
                'options' => $this->options($name),
            ];
        }

        return [
            'action' => $endpoint['action'],
            'name' => $endpoint['name'],
            'group' => $endpoint['write'] ? 'Create in BUSY' : $endpoint['group'],
            'write' => $endpoint['write'],
            'fields' => $fields,
        ];
    }

    private function label(string $name): string
    {
        return match ($name) {
            'Alias', 'ERP_Alias' => 'Party code',
            'Start_date', 'sd' => 'From date',
            'End_date', 'ed' => 'To date',
            'PriceDate', 'Tran_date' => 'Date',
            'Grdate' => 'GR date',
            'Pdc' => 'Include post-dated cheques',
            'vchtype', 'VchType' => 'Voucher type',
            'ActCode', 'Act_code', 'ActErpCode', 'BusyMastCode' => 'Account code',
            'Cash_bank_code' => 'Cash or bank code',
            'GrpCode', 'Parent_grp_code' => 'Group code',
            'Parent_grp_name' => 'Group name',
            'OrderNo' => 'Order number',
            'ItemErpCode', 'ERP_code' => 'Item or account code',
            'finyr' => 'Financial year',
            'vchcode' => 'Voucher code',
            'RecType' => 'Record type',
            'VoucherNo' => 'Voucher number',
            'Voucher_amt' => 'Amount',
            'Narration' => 'Note',
            'Item_data' => 'Item lines',
            'Print_name' => 'Print name',
            'Gst_no' => 'GST number',
            'Op_balance' => 'Opening balance',
            'Op_stock' => 'Opening stock',
            'Saleprice' => 'Sale price',
            'Disc_percentage' => 'Discount %',
            'Tax_cat' => 'Tax category',
            'Vehicle_no' => 'Vehicle number',
            'Grno' => 'GR number',
            'Enable_param' => 'Enable parameters',
            'Enable_batch' => 'Enable batch',
            'Enable_serialno' => 'Enable serial number',
            default => ucfirst(str_replace('_', ' ', $name)),
        };
    }

    private function type(string $name): string
    {
        if (in_array($name, ['Start_date', 'End_date', 'sd', 'ed', 'PriceDate', 'Tran_date', 'Grdate'], true)) {
            return 'date';
        }
        if (in_array($name, ['vchtype', 'VchType', 'Pdc', 'Blocked', 'Deactive', 'Enable_param', 'Enable_batch', 'Enable_serialno'], true)) {
            return 'select';
        }
        if ($name === 'Item_data') {
            return 'textarea';
        }

        return 'text';
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function options(string $name): array
    {
        if (in_array($name, ['vchtype', 'VchType'], true)) {
            return [
                ['value' => '9', 'label' => 'Sale'],
                ['value' => '2', 'label' => 'Purchase'],
                ['value' => '3', 'label' => 'Sale return'],
                ['value' => '10', 'label' => 'Purchase return'],
                ['value' => '12', 'label' => 'Sales order'],
                ['value' => '13', 'label' => 'Purchase order'],
                ['value' => '14', 'label' => 'Receipt'],
                ['value' => '19', 'label' => 'Payment'],
                ['value' => '16', 'label' => 'Journal'],
            ];
        }

        if ($name === 'Pdc') {
            return [
                ['value' => 'N', 'label' => 'No'],
                ['value' => 'Y', 'label' => 'Yes'],
            ];
        }

        if (in_array($name, ['Blocked', 'Deactive', 'Enable_param', 'Enable_batch', 'Enable_serialno'], true)) {
            return [
                ['value' => 'N', 'label' => 'No'],
                ['value' => 'Y', 'label' => 'Yes'],
            ];
        }

        return [];
    }

    private function financialYearStart(Carbon $today): Carbon
    {
        $start = $today->copy()->month(4)->day(1)->startOfDay();
        if ($today->lt($start)) {
            $start->subYear();
        }

        return $start;
    }
}
