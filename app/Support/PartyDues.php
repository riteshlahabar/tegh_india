<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Party;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * What a customer owes right now -- the figure the party drawer prints as
 * "Total Outstanding".
 *
 * This exists because the drawer and the WhatsApp templates used to state two
 * different amounts: the drawer summed every pending invoice for the party,
 * while a template quoted the one invoice the button happened to be wired to.
 * The reminder read as wrong even though neither number was. There is now one
 * definition of "pending dues" and both read it.
 */
final class PartyDues
{
    private function __construct(
        public readonly float $total,
        public readonly int $count,
        public readonly ?Carbon $oldestDueDate,
    ) {}

    /**
     * Build from invoices that have ALREADY been through Invoice::visibleTo().
     * The drawer has them loaded, so this costs no extra query.
     */
    public static function fromInvoices($invoices): self
    {
        $pending = self::pending($invoices);
        $oldest = $pending->sortBy('due_date')->first();

        return new self(
            (float) $pending->sum('outstanding'),
            $pending->count(),
            $oldest?->due_date,
        );
    }

    public static function forParty(?Party $party): self
    {
        if (! $party) {
            return new self(0.0, 0, null);
        }

        return self::fromInvoices(
            Invoice::query()
                ->where('party_id', $party->id)
                ->visibleTo(auth()->user())
                ->get()
        );
    }

    public static function forInvoice(Invoice $invoice): self
    {
        $invoice->loadMissing('party');

        return self::forParty($invoice->party);
    }

    /**
     * Outstanding left to collect and already due. Nulls are excluded by the
     * date check, so an invoice with no due date never counts as pending.
     *
     * @return Collection<int, Invoice>
     */
    public static function pending($invoices): Collection
    {
        return collect($invoices)
            ->filter(
                fn (Invoice $invoice) => (float) $invoice->outstanding > 0
                    && $invoice->due_date
                    && $invoice->due_date->lte(today())
            )
            ->values();
    }

    public function totalInr(): string
    {
        return inr($this->total);
    }

    /** "12 pending invoices" / "1 pending invoice". */
    public function invoiceLabel(): string
    {
        return $this->count.' pending '.($this->count === 1 ? 'invoice' : 'invoices');
    }

    /**
     * True when quoting the party total would misstate a single bill -- i.e.
     * there is more than one pending invoice, so naming just one is wrong.
     */
    public function spansMultipleInvoices(): bool
    {
        return $this->count > 1;
    }
}
