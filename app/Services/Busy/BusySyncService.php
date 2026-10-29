<?php

namespace App\Services\Busy;

use App\Models\AppSetting;
use App\Models\BusySyncLog;
use App\Models\Invoice;
use App\Models\Party;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BusySyncService
{
    /** @var array<string, Party> */
    private array $partyCache = [];

    public function __construct(private BusyApiClient $client) {}

    /**
     * @return array{parties_upserted: int, invoices_upserted: int, invoices_closed: int, log: BusySyncLog}
     */
    public function sync(string $triggeredBy = 'system', ?string $from = null, ?string $to = null): array
    {
        $from = $from ?: $this->financialYearStart()->toDateString();
        $to = $to ?: now('Asia/Kolkata')->toDateString();

        $log = BusySyncLog::create([
            'triggered_by' => $triggeredBy,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $partiesUpserted = $this->syncParties();
            [$invoicesUpserted, $seenIds] = $this->syncOutstanding();
            $invoicesUpserted += $this->enrichFromSalesVouchers($from, $to);
            $this->applyCancelledSales($from, $to);
            $invoicesClosed = $this->closeMissingOutstanding($seenIds);

            $log->update([
                'status' => 'success',
                'parties_upserted' => $partiesUpserted,
                'invoices_upserted' => $invoicesUpserted,
                'invoices_closed' => $invoicesClosed,
                'finished_at' => now(),
            ]);

            AppSetting::setValue('busy_status', 'Connected');
            AppSetting::setValue('busy_last_sync', now()->format('d M Y – h:i A'));

            return [
                'parties_upserted' => $partiesUpserted,
                'invoices_upserted' => $invoicesUpserted,
                'invoices_closed' => $invoicesClosed,
                'log' => $log->fresh(),
            ];
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);
            AppSetting::setValue('busy_status', 'Error');
            Log::error('BUSY sync failed', ['exception' => $e]);

            throw $e instanceof BusyApiException
                ? $e
                : new BusyApiException('BUSY sync failed: '.$e->getMessage(), 0, $e);
        }
    }

    public function financialYearStart(?Carbon $now = null): Carbon
    {
        $now ??= now('Asia/Kolkata');
        $start = $now->copy()->month(4)->day(1)->startOfDay();
        if ($now->lt($start)) {
            $start->subYear();
        }

        return $start;
    }

    private function syncParties(): int
    {
        $this->warmPartyCache();
        $rows = BusyPayload::rows($this->client->getAccounts());
        $count = 0;

        foreach ($rows as $row) {
            $this->upsertParty($row);
            $count++;
        }

        return $count;
    }

    private function warmPartyCache(): void
    {
        if (! empty($this->partyCache)) {
            return;
        }

        foreach ($this->parties()->get() as $p) {
            if ($p->busy_party_id !== null && $p->busy_party_id !== '') {
                $this->partyCache[(string) $p->busy_party_id] = $p;
            }
            if ($p->code !== null && $p->code !== '') {
                $this->partyCache[(string) $p->code] = $p;
            }
        }
    }

    /**
     * @return array{0: int, 1: list<int>}
     */
    private function syncOutstanding(): array
    {
        $rows = BusyPayload::rows($this->client->getOutstanding());
        $count = 0;
        $seen = [];

        foreach ($rows as $row) {
            $party = $this->upsertParty($row);
            $invoice = $this->upsertInvoice($row, $party);
            if ($invoice !== null) {
                $seen[] = $invoice->id;
                $count++;
            }
        }

        return [$count, array_values(array_unique($seen))];
    }

    private function enrichFromSalesVouchers(string $from, string $to): int
    {
        try {
            $rows = BusyPayload::rows($this->client->getListOfVouchers(BusyEndpoints::VOUCHER_SALE, $from, $to));
        } catch (BusyApiException $e) {
            Log::warning('BUSY sales voucher list skipped', ['error' => $e->getMessage()]);

            return 0;
        }

        $count = 0;
        foreach ($rows as $row) {
            $party = $this->upsertParty($row);
            $invoice = $this->upsertInvoice($row, $party, enrichOnly: true);
            if ($invoice !== null) {
                $count++;
            }
        }

        return $count;
    }

    private function applyCancelledSales(string $from, string $to): void
    {
        try {
            $rows = BusyPayload::rows($this->client->getCancelledVoucherList(BusyEndpoints::VOUCHER_SALE, $from, $to));
        } catch (BusyApiException $e) {
            Log::warning('BUSY cancelled voucher list skipped', ['error' => $e->getMessage()]);

            return;
        }

        foreach ($rows as $row) {
            $invoiceNo = (string) BusyPayload::pick($row, ['VoucherNumber', 'BillNo', 'VchNo', 'invoice_no'], '');
            if ($invoiceNo === '') {
                continue;
            }

            $this->invoices()->where('invoice_no', $invoiceNo)->get()->each(function (Invoice $invoice) {
                $invoice->outstanding = 0;
                $invoice->status = 'Paid';
                $invoice->save();
            });
        }
    }

    /**
     * @param  list<int>  $seenInvoiceIds
     */
    private function closeMissingOutstanding(array $seenInvoiceIds): int
    {
        if ($seenInvoiceIds === []) {
            return 0;
        }

        $closed = 0;
        $this->invoices()
            ->whereNotNull('busy_invoice_id')
            ->where('outstanding', '>', 0)
            ->whereNotIn('id', $seenInvoiceIds)
            ->get()
            ->each(function (Invoice $invoice) use (&$closed) {
                $invoice->received_amount = $invoice->invoice_amount;
                $invoice->outstanding = 0;
                $invoice->status = 'Paid';
                $invoice->save();
                $closed++;
            });

        return $closed;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function upsertParty(array $row): Party
    {
        $busyId = (string) BusyPayload::pick($row, [
            'ERP_code', 'ERPCode', 'ErpCode', 'MasterCode', 'Mast_ID', 'Act_code', 'ActCode', 'BusyMastCode',
            'busy_party_id', 'PartyCode', 'AccCode',
        ], '');
        $code = (string) BusyPayload::pick($row, ['Alias', 'PartyCode', 'AccCode', 'code', 'ErpCode', 'ERPCode'], $busyId);
        $name = (string) BusyPayload::pick($row, ['Name', 'PartyName', 'AccName', 'Account_Name', 'Account', 'Print_name', 'PrintName'], 'Unknown Party');

        if ($busyId === '') {
            $busyId = $code !== '' ? $code : Str::slug($name);
        }
        if ($code === '') {
            $code = $busyId;
        }

        $party = $this->partyCache[$busyId] ?? $this->partyCache[$code] ?? null;
        if (! $party) {
            $party = $this->parties()
                ->where(function (Builder $query) use ($busyId, $code) {
                    $query->where('busy_party_id', $busyId)->orWhere('code', $code);
                })
                ->first();
        }

        $attributes = [
            'name' => $name,
            'code' => $this->uniquePartyCode($code, $party?->id),
            'busy_party_id' => $busyId,
            'contact_person' => BusyPayload::pick($row, ['Contact', 'Print_name', 'PrintName', 'ContactPerson', 'contact_person'], $party?->contact_person),
            'mobile' => BusyPayload::pick($row, ['Mobile_No', 'WhatsAppNo', 'Mobile', 'mobile', 'Phone', 'TelNo'], $party?->mobile),
            'email' => BusyPayload::pick($row, ['Email', 'email'], $party?->email),
            'city' => BusyPayload::city($row, $party?->city),
            'state' => BusyPayload::pick($row, ['State_Name', 'State', 'state'], $party?->state),
            'gstin' => BusyPayload::pick($row, ['GST_No', 'Gst_no', 'GSTNo', 'Gstin', 'GSTIN'], $party?->gstin ?? null),
            'sales_person' => BusyPayload::pick($row, ['SalesPerson', 'sales_person', 'Salesman'], $party?->sales_person),
            'branch' => BusyPayload::pick($row, ['Branch', 'branch'], $party?->branch),
            'credit_limit' => BusyPayload::money(BusyPayload::pick($row, ['CreditLimit', 'credit_limit'], $party?->credit_limit ?? 0)),
            'payment_terms' => BusyPayload::pick($row, ['PaymentTerms', 'payment_terms'], $party?->payment_terms),
        ];

        if ($party) {
            $this->partyCache[(string) $party->busy_party_id] = $party;
            $this->partyCache[(string) $party->code] = $party;
            unset($attributes['code']);
            $party->fill($attributes);
            if ($party->isDirty()) {
                $party->save();
            }

            return $party;
        }

        $party = Party::create($attributes);
        $this->partyCache[(string) $party->busy_party_id] = $party;
        $this->partyCache[(string) $party->code] = $party;

        return $party;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function upsertInvoice(array $row, Party $party, bool $enrichOnly = false): ?Invoice
    {
        $invoiceNo = (string) BusyPayload::pick($row, ['Inv_No', 'BillNo', 'VchNo', 'VoucherNo', 'VoucherNumber', 'invoice_no', 'InvoiceNo'], '');
        $busyId = (string) BusyPayload::pick($row, ['VchCode', 'Vchcode', 'busy_invoice_id', 'VoucherCode'], '');
        $invoiceDateHint = BusyPayload::date(BusyPayload::pick($row, ['Inv_Date', 'BillDate', 'VchDate', 'Tran_date', 'invoice_date', 'Date']));

        if ($invoiceNo === '' && $busyId === '') {
            return null;
        }
        if ($invoiceNo === '') {
            $invoiceNo = $busyId;
        }

        $invoice = $this->invoices()->where('invoice_no', $invoiceNo)->first()
            ?? ($busyId !== '' ? $this->invoices()->where('busy_invoice_id', $busyId)->first() : null);

        $invoiceDate = $invoiceDateHint
            ?: ($invoice?->invoice_date?->toDateString() ?? now()->toDateString());
        $dueDate = BusyPayload::date(BusyPayload::pick($row, ['DueDate', 'due_date']))
            ?: ($invoice?->due_date?->toDateString() ?? Carbon::parse($invoiceDate)->addDays(30)->toDateString());

        $narration = BusyPayload::pick($row, ['Narration', 'narration', 'VchNarration', 'ItemNarration', 'Remarks', 'remarks', 'Note', 'note', 'PONo', 'po_no', 'OrderNo'], $invoice?->narration ?? $invoice?->po_no);

        if ($enrichOnly) {
            if (! $invoice) {
                return null;
            }

            $attributes = [];
            if ($invoiceDateHint && ! $invoice->invoice_date) {
                $attributes['invoice_date'] = $invoiceDateHint;
            }
            if (! empty($dueDate) && ! $invoice->due_date) {
                $attributes['due_date'] = $dueDate;
            }
            if ($narration !== null && $narration !== '') {
                $attributes['narration'] = $narration;
                $attributes['po_no'] = $narration;
            }
            $salesPerson = BusyPayload::pick($row, ['SalesPerson', 'sales_person']);
            if ($salesPerson !== null && $salesPerson !== '') {
                $attributes['sales_person'] = $salesPerson;
            }
            $branch = BusyPayload::pick($row, ['Branch', 'branch']);
            if ($branch !== null && $branch !== '') {
                $attributes['branch'] = $branch;
            }

            if (! empty($attributes)) {
                $invoice->fill($attributes);
                $invoice->save();
            }

            return $invoice;
        }

        if ($busyId === '') {
            $busyId = $invoice?->busy_invoice_id ?: implode('|', array_filter([
                (string) $party->busy_party_id,
                $invoiceNo,
                $invoiceDateHint ?: 'na',
            ], fn ($part) => $part !== ''));
        }

        $invoiceAmount = BusyPayload::money(BusyPayload::pick($row, ['Inv_Amount', 'BillAmt', 'Voucher_amt', 'Amount', 'invoice_amount', 'Total', 'Nettamount'], $invoice?->invoice_amount ?? 0));
        $outstandingRaw = BusyPayload::pick($row, ['Outstanding_Amt', 'BalAmt', 'Outstanding', 'outstanding', 'Balance']);
        $receivedRaw = BusyPayload::pick($row, ['RecdAmt', 'Received', 'received_amount']);

        if ($outstandingRaw !== null) {
            $outstanding = BusyPayload::money($outstandingRaw);
            $received = $receivedRaw !== null
                ? BusyPayload::money($receivedRaw)
                : max($invoiceAmount - $outstanding, 0);
        } else {
            $received = $receivedRaw !== null ? BusyPayload::money($receivedRaw) : (float) ($invoice?->received_amount ?? 0);
            $outstanding = max($invoiceAmount - $received, 0);
        }

        $status = $outstanding <= 0 ? 'Paid' : ($invoice?->status ?? 'Not Due');

        $attributes = [
            'party_id' => $party->id,
            'invoice_no' => $this->uniqueInvoiceNo($invoiceNo, $invoice?->id),
            'busy_invoice_id' => $busyId,
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'invoice_amount' => $invoiceAmount > 0 ? $invoiceAmount : ($invoice?->invoice_amount ?? 0),
            'received_amount' => $received,
            'outstanding' => $outstanding,
            'status' => $status,
            'sales_person' => BusyPayload::pick($row, ['SalesPerson', 'sales_person'], $invoice?->sales_person ?? $party->sales_person),
            'branch' => BusyPayload::pick($row, ['Branch', 'branch'], $invoice?->branch ?? $party->branch),
            'po_no' => $narration,
            'narration' => $narration,
        ];

        if ($invoice) {
            unset($attributes['invoice_no']);
            $invoice->fill($attributes);
            $invoice->refreshStoredStatus();

            return $invoice;
        }

        $created = Invoice::create(array_merge($attributes, [
            'assigned_to' => $party->assigned_to,
        ]));
        $created->refreshStoredStatus();

        return $created;
    }

    private function uniquePartyCode(string $code, ?int $ignoreId): string
    {
        $base = $code;
        $suffix = 1;
        while ($this->parties()->where('code', $code)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $code = $base.'-'.$suffix++;
        }

        return $code;
    }

    private function uniqueInvoiceNo(string $invoiceNo, ?int $ignoreId): string
    {
        $base = $invoiceNo;
        $suffix = 1;
        while ($this->invoices()->where('invoice_no', $invoiceNo)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $invoiceNo = $base.'-'.$suffix++;
        }

        return $invoiceNo;
    }

    private function parties(): Builder
    {
        return Party::withoutGlobalScope('busy_source');
    }

    private function invoices(): Builder
    {
        return Invoice::withoutGlobalScope('busy_source');
    }
}
