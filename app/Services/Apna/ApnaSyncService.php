<?php

namespace App\Services\Apna;

use App\Models\ApnaAccount;
use App\Models\ApnaGroup;
use App\Models\ApnaItem;
use App\Models\ApnaOutstanding;
use App\Models\ApnaPendingOrder;
use App\Models\ApnaSyncLog;
use App\Models\ApnaVoucher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class ApnaSyncService
{
    public function __construct(private ApnaBazarClient $client) {}

    /**
     * Pull read-only Apna Bazar lists into the CRM.
     *
     * @return array<string, int>
     */
    public function sync(): array
    {
        $now = now();
        $counts = [];

        $counts['accounts'] = $this->syncAccounts($this->client->accounts(), $now);
        $this->log('get_accounts.php', $counts['accounts']);

        $counts['items'] = $this->syncItems($this->client->items(), $now);
        $this->log('get_items.php', $counts['items']);

        $counts['account_groups'] = $this->syncGroups('account', $this->client->accountGroups(), $now);
        $this->log('get_account_group.php', $counts['account_groups']);

        $counts['item_groups'] = $this->syncGroups('item', $this->client->itemGroups(), $now);
        $this->log('get_item_group.php', $counts['item_groups']);

        $counts['outstanding'] = $this->syncOutstanding($this->client->outstanding(), $now);
        $this->log('get_outstanding.php', $counts['outstanding']);

        $sales = $this->client->inventoryVouchers('9');
        $receipts = $this->client->accountingVouchers('14');
        $counts['vouchers'] = $this->syncVouchers($sales, $receipts, $now);
        $this->log('get_inventoryvch_list.php', count($sales));
        $this->log('get_accountingvch_list.php', count($receipts));

        $start = '2022-04-01';
        $end = now()->addYear()->toDateString();
        $counts['pending_orders'] = $this->syncPending($this->client->pendingOrders($start, $end), $now);
        $this->log('get_busypendingorder.php', $counts['pending_orders']);

        return $counts;
    }

    public function summaryMessage(array $counts): string
    {
        return sprintf(
            'Apna Bazar updated: %d accounts, %d items, %d outstanding bills, %d vouchers, %d pending orders.',
            $counts['accounts'] ?? 0,
            $counts['items'] ?? 0,
            $counts['outstanding'] ?? 0,
            $counts['vouchers'] ?? 0,
            $counts['pending_orders'] ?? 0,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function syncAccounts(array $rows, Carbon $now): int
    {
        $codes = [];

        DB::transaction(function () use ($rows, $now, &$codes) {
            foreach ($rows as $row) {
                $code = $this->int($this->pick($row, ['ErpCode', 'ERPCode', 'Mast_ID']));
                $name = trim((string) $this->pick($row, ['Name', 'PrintName'], ''));
                if ($code === null || $name === '') {
                    continue;
                }

                $address = collect(['Address1', 'Address2', 'Address3', 'Address4'])
                    ->map(fn (string $key) => trim((string) ($row[$key] ?? '')))
                    ->filter()
                    ->implode(', ');

                ApnaAccount::query()->updateOrCreate(
                    ['erp_code' => $code],
                    [
                        'name' => $name,
                        'alias' => $this->text($this->pick($row, ['Alias'])),
                        'print_name' => $this->text($this->pick($row, ['PrintName'])),
                        'parent_group' => $this->text($this->pick($row, ['Parent_Group'])),
                        'mobile' => $this->text($this->pick($row, ['Mobile_No', 'TelNo'])),
                        'whatsapp' => $this->text($this->pick($row, ['WhatsAppNo'])),
                        'email' => $this->text($this->pick($row, ['Email'])),
                        'gst_number' => $this->text($this->pick($row, ['GST_No'])),
                        'contact' => $this->text($this->pick($row, ['Contact'])),
                        'state' => $this->text($this->pick($row, ['State_Name'])),
                        'pin_code' => $this->text($this->pick($row, ['PinCode'])),
                        'address' => $address !== '' ? $address : null,
                        'credit_limit' => $this->money($this->pick($row, ['CreditLimit'])),
                        'credit_days' => $this->int($this->pick($row, ['CreditDays'])),
                        'closing_balance' => $this->money($this->pick($row, ['Closing_Balance'])),
                        'master_status' => $this->int($this->pick($row, ['MasterSatus', 'MasterStatus'])),
                        'synced_at' => $now,
                    ]
                );
                $codes[] = $code;
            }

            ApnaAccount::query()->when(
                $codes !== [],
                fn ($query) => $query->whereNotIn('erp_code', $codes),
                fn ($query) => $query
            )->delete();
        });

        return count($codes);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function syncItems(array $rows, Carbon $now): int
    {
        $codes = [];

        DB::transaction(function () use ($rows, $now, &$codes) {
            foreach ($rows as $row) {
                $code = $this->int($this->pick($row, ['ErpCode', 'Mast_ID']));
                $name = trim((string) $this->pick($row, ['Name', 'PrintName'], ''));
                if ($code === null || $name === '') {
                    continue;
                }

                ApnaItem::query()->updateOrCreate(
                    ['erp_code' => $code],
                    [
                        'name' => $name,
                        'alias' => $this->text($this->pick($row, ['Alias'])),
                        'print_name' => $this->text($this->pick($row, ['PrintName'])),
                        'parent_group' => $this->text($this->pick($row, ['Parent_Group'])),
                        'unit' => $this->text($this->pick($row, ['Unit'])),
                        'hsn_code' => $this->text($this->pick($row, ['HSN_Code'])),
                        'sale_price' => $this->money($this->pick($row, ['SalePrice'])),
                        'mrp' => $this->money($this->pick($row, ['MRP'])),
                        'closing_stock' => $this->money($this->pick($row, ['Closing_Stock'])),
                        'tax_percentage' => $this->money($this->pick($row, ['Tax_Percentage'])),
                        'master_status' => $this->int($this->pick($row, ['MasterSatus', 'MasterStatus'])),
                        'synced_at' => $now,
                    ]
                );
                $codes[] = $code;
            }

            ApnaItem::query()->when(
                $codes !== [],
                fn ($query) => $query->whereNotIn('erp_code', $codes)
            )->delete();
        });

        return count($codes);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function syncGroups(string $kind, array $rows, Carbon $now): int
    {
        $codes = [];

        DB::transaction(function () use ($kind, $rows, $now, &$codes) {
            foreach ($rows as $row) {
                $code = $this->int($this->pick($row, ['Erpcode', 'ErpCode']));
                $name = trim((string) $this->pick($row, ['Name'], ''));
                if ($code === null || $name === '') {
                    continue;
                }

                ApnaGroup::query()->updateOrCreate(
                    ['kind' => $kind, 'erp_code' => $code],
                    [
                        'name' => $name,
                        'parent_name' => $this->text($this->pick($row, ['ParentGroupName'])),
                        'busy_mast_code' => $this->int($this->pick($row, ['BusyMastCode'])),
                        'synced_at' => $now,
                    ]
                );
                $codes[] = $code;
            }

            ApnaGroup::query()->where('kind', $kind)->when(
                $codes !== [],
                fn ($query) => $query->whereNotIn('erp_code', $codes)
            )->delete();
        });

        return count($codes);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function syncOutstanding(array $rows, Carbon $now): int
    {
        $saved = 0;

        DB::transaction(function () use ($rows, $now, &$saved) {
            ApnaOutstanding::query()->delete();
            foreach ($rows as $row) {
                ApnaOutstanding::query()->create([
                    'erp_code' => $this->int($this->pick($row, ['ERPCode', 'ErpCode'])),
                    'account_name' => $this->text($this->pick($row, ['Account_Name'])),
                    'invoice_no' => $this->text($this->pick($row, ['Inv_No'])),
                    'invoice_date' => $this->date($this->pick($row, ['Inv_Date'])),
                    'due_date' => $this->date($this->pick($row, ['DueDate'])),
                    'invoice_amount' => $this->money($this->pick($row, ['Inv_Amount'])),
                    'outstanding_amount' => $this->money($this->pick($row, ['Outstanding_Amt'])),
                    'synced_at' => $now,
                ]);
                $saved++;
            }
        });

        return $saved;
    }

    /**
     * @param  list<array<string, mixed>>  $sales
     * @param  list<array<string, mixed>>  $receipts
     */
    private function syncVouchers(array $sales, array $receipts, Carbon $now): int
    {
        $saved = 0;

        DB::transaction(function () use ($sales, $receipts, $now, &$saved) {
            ApnaVoucher::query()->delete();

            foreach ($sales as $row) {
                if ($this->storeVoucher('inventory', $row, $now)) {
                    $saved++;
                }
            }
            foreach ($receipts as $row) {
                if ($this->storeVoucher('accounting', $row, $now)) {
                    $saved++;
                }
            }
        });

        return $saved;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function storeVoucher(string $kind, array $row, Carbon $now): bool
    {
        $id = $this->text($this->pick($row, ['Voucher_id', 'voucher_id']));
        if ($id === null) {
            return false;
        }

        ApnaVoucher::query()->updateOrCreate(
            ['kind' => $kind, 'voucher_id' => $id],
            [
                'voucher_no' => $this->text($this->pick($row, ['VchNo'])),
                'voucher_type' => $this->text($this->pick($row, ['VchType'])),
                'status' => $this->text($this->pick($row, ['Status'])),
                'tran_date' => $this->date($this->pick($row, ['TranDate', 'OrderDate'])),
                'amount' => $this->money($this->pick($row, ['Amount'])),
                'narration' => $this->text($this->pick($row, ['Narration'])),
                'synced_at' => $now,
            ]
        );

        return true;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function syncPending(array $rows, Carbon $now): int
    {
        $saved = 0;

        DB::transaction(function () use ($rows, $now, &$saved) {
            ApnaPendingOrder::query()->delete();
            foreach ($rows as $row) {
                ApnaPendingOrder::query()->create([
                    'item' => $this->text($this->pick($row, ['Item'])),
                    'account_name' => $this->text($this->pick($row, ['Account'])),
                    'order_ref' => $this->text($this->pick($row, ['Order_RefNo'])),
                    'order_date' => $this->date($this->pick($row, ['Date'])),
                    'due_date' => $this->date($this->pick($row, ['DueDate'])),
                    'order_qty' => $this->money($this->pick($row, ['OrderQty'])),
                    'pending_qty' => $this->money($this->pick($row, ['PendingOrder'])),
                    'synced_at' => $now,
                ]);
                $saved++;
            }
        });

        return $saved;
    }

    private function log(string $endpoint, int $count, string $status = 'ok', ?string $message = null): void
    {
        ApnaSyncLog::query()->create([
            'endpoint' => $endpoint,
            'status' => $status,
            'record_count' => $count,
            'message' => $message,
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $keys
     */
    private function pick(array $row, array $keys, mixed $default = null): mixed
    {
        $lower = [];
        foreach ($row as $key => $value) {
            $lower[strtolower((string) $key)] = $value;
        }

        foreach ($keys as $key) {
            $value = $lower[strtolower($key)] ?? null;
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }

    private function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function int(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function money(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $clean = str_replace([',', ' '], '', (string) $value);
        if (! is_numeric($clean)) {
            return null;
        }

        return number_format((float) $clean, 2, '.', '');
    }

    private function date(mixed $value): ?string
    {
        $text = $this->text($value);
        if ($text === null) {
            return null;
        }

        try {
            return Carbon::parse($text)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
