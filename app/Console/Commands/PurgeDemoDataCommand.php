<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Party;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeDemoDataCommand extends Command
{
    protected $signature = 'crm:purge-demo';

    protected $description = 'Delete seeded dummy parties and invoices so the CRM only shows BUSY-synced records';

    public function handle(): int
    {
        $partyCount = Party::withoutGlobalScopes()->where('busy_party_id', 'like', 'BUSY-%')->count();
        $invoiceCount = Invoice::withoutGlobalScopes()->where('busy_invoice_id', 'like', 'BINV-%')->count();

        DB::transaction(function () {
            Invoice::withoutGlobalScopes()->where('busy_invoice_id', 'like', 'BINV-%')->delete();
            Party::withoutGlobalScopes()->where('busy_party_id', 'like', 'BUSY-%')->delete();
        });

        $this->info("Removed {$partyCount} demo parties and {$invoiceCount} demo invoices. Lists now show BUSY records only.");

        return self::SUCCESS;
    }
}
