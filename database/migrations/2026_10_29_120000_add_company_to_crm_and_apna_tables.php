<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-company support for the Apna Bazar / BUSY integration.
 *
 * One Apna Bazar login == one company's books. The CRM held a single credential
 * (Mumbai, 9082479995), so Gujarat and Pune were never fetched at all. Before they
 * can be synced, every synced row has to record WHICH company it came from.
 *
 * This is mandatory, not cosmetic: BUSY restarts ERP codes per company. Measured
 * against the three live datasets on 2026-10-29:
 *
 *     MUM vs GUJ:  8 shared ERP codes,  8 are DIFFERENT parties
 *     MUM vs PUN: 13 shared ERP codes, 12 are DIFFERENT parties
 *
 *     ERP 153  -> Mumbai  "POLYCAB INDIA LTD RECEIVABLE A/C"
 *                 Gujarat "YCube Engineered Solutions Pvt Ltd."
 *
 * With apna_accounts.erp_code still UNIQUE on its own, syncing Gujarat would rename
 * Polycab to YCube and reattach its invoices. parties.code and invoices.invoice_no
 * carry the same risk, and BusySyncService::uniquePartyCode() would silently append
 * suffixes to real Gujarat parties rather than fail loudly.
 *
 * Every row that exists today came from the Mumbai login, so rows are backfilled to
 * 'mumbai' before the composite unique keys go on.
 */
return new class extends Migration
{
    private const DEFAULT_COMPANY = 'mumbai';

    /**
     * table => [[unique columns BEFORE], [unique columns AFTER]]
     */
    private const UNIQUE_REBUILDS = [
        'apna_accounts' => [['erp_code'], ['company', 'erp_code']],
        'apna_groups' => [['kind', 'erp_code'], ['company', 'kind', 'erp_code']],
        'apna_vouchers' => [['kind', 'voucher_id'], ['company', 'kind', 'voucher_id']],
        'parties' => [['code'], ['company', 'code']],
        'invoices' => [['invoice_no'], ['company', 'invoice_no']],
    ];

    /**
     * table => column the new `company` column is placed after
     */
    private const COMPANY_COLUMNS = [
        'apna_accounts' => 'erp_code',
        'apna_items' => 'erp_code',
        'apna_groups' => 'erp_code',
        'apna_outstandings' => 'erp_code',
        'apna_vouchers' => 'voucher_id',
        'apna_pending_orders' => 'item',
        'apna_sync_logs' => 'endpoint',
        'parties' => 'name',
        'invoices' => 'invoice_no',
    ];

    public function up(): void
    {
        // 1. Add the column everywhere it is missing.
        foreach (self::COMPANY_COLUMNS as $table => $after) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'company')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($after) {
                $blueprint->string('company', 32)->nullable()->after($after);
            });
        }

        // 2. Backfill. Everything synced so far came from the Mumbai credential.
        foreach (array_keys(self::COMPANY_COLUMNS) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company')) {
                continue;
            }

            DB::table($table)->whereNull('company')->update(['company' => self::DEFAULT_COMPANY]);
        }

        // 3. Index for the Outstanding page filter, which currently guesses the region
        //    from a hardcoded city-name whitelist in OutstandingPageService::70-83.
        foreach (array_keys(self::COMPANY_COLUMNS) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company')) {
                continue;
            }

            $this->attempt(fn () => Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->index('company');
            }));
        }

        // 4. Rebuild the unique keys so ERP codes, party codes and invoice numbers are
        //    only unique WITHIN a company.
        foreach (self::UNIQUE_REBUILDS as $table => [$before, $afterCols]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company')) {
                continue;
            }

            $this->attempt(fn () => Schema::table($table, function (Blueprint $blueprint) use ($before) {
                $blueprint->dropUnique($before);
            }));

            $this->attempt(fn () => Schema::table($table, function (Blueprint $blueprint) use ($afterCols) {
                $blueprint->unique($afterCols);
            }));
        }
    }

    public function down(): void
    {
        foreach (self::UNIQUE_REBUILDS as $table => [$before, $afterCols]) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $this->attempt(fn () => Schema::table($table, function (Blueprint $blueprint) use ($afterCols) {
                $blueprint->dropUnique($afterCols);
            }));

            $this->attempt(fn () => Schema::table($table, function (Blueprint $blueprint) use ($before) {
                $blueprint->unique($before);
            }));
        }

        foreach (array_keys(self::COMPANY_COLUMNS) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company')) {
                continue;
            }

            $this->attempt(fn () => Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropIndex(['company']);
            }));

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('company');
            });
        }
    }

    /**
     * Index operations are not transactional in MySQL and the exact index names differ
     * between the cPanel database and a fresh migrate. Mirrors the defensive style
     * already used in 2026_08_29_111400_add_busy_integration_support.php.
     */
    private function attempt(callable $operation): void
    {
        try {
            $operation();
        } catch (\Throwable $e) {
            // Index already in the desired state.
        }
    }
};
