<?php

namespace App\Support;

use App\Models\Invoice;

class WhatsappTemplates
{
    /**
     * @return array<string, callable(Invoice): string>
     */
    public static function all(): array
    {
        return [
            // 'Due Today' and 'Overdue Payment' were dropped (2026-11-04): once
            // the reminder quotes the party's whole pending ledger it already
            // says everything those two said, and three near-identical choices
            // only slowed the telecaller down. render() falls back to this
            // template, so an older mobile build still asking for one of them
            // gets the reminder rather than an error.
            'Payment Reminder' => fn (Invoice $r) => self::paymentReminder($r),
            'Payment Promise Reminder' => fn (Invoice $r) => self::promise($r),
            'Statement Request' => fn (Invoice $r) => self::statement($r),
            'Payment Confirmation' => fn (Invoice $r) => self::confirmation($r),
            'Thank You for Payment' => fn (Invoice $r) => self::thankYou($r),
        ];
    }

    public static function names(): array
    {
        return array_keys(self::all());
    }

    public static function render(string $name, Invoice $invoice): string
    {
        $all = self::all();
        $fn = $all[$name] ?? $all['Payment Reminder'];

        return $fn($invoice);
    }

    private static function party(Invoice $r): string
    {
        return $r->party?->name ?? 'Customer';
    }

    /**
     * The reminder quotes the customer's TOTAL pending dues -- the same figure
     * the party drawer shows -- not the one invoice the WhatsApp button
     * happened to be wired to. When the party has exactly one pending invoice
     * the two are the same number, and the older wording that names that
     * invoice is kept because it is more useful to the customer.
     *
     * It covers both of the templates that used to sit beside it: an overdue
     * ledger and a bill falling due today are both just "what is pending as of
     * today", which is what PartyDues measures.
     */
    private static function paymentReminder(Invoice $r): string
    {
        $dues = PartyDues::forInvoice($r);

        $subject = $dues->spansMultipleInvoices()
            ? $dues->totalInr().' against '.$dues->invoiceLabel().', the oldest of which was due on '.crm_date($dues->oldestDueDate)
            : inr($r->outstanding)." against Invoice No. {$r->invoice_no}, which was due on ".crm_date($r->due_date);

        return 'Dear '.self::party($r).",\n\nThis is a gentle reminder regarding the outstanding payment of ".$subject.".\n\nKindly arrange the payment at the earliest.\n\nRegards,\nTegh Cables India Pvt. Ltd.";
    }

    /**
     * Deliberately NOT the party total: a promise is a specific amount the
     * customer agreed to on a specific date, and overriding it with the full
     * ledger would contradict the conversation it refers to.
     */
    private static function promise(Invoice $r): string
    {
        $ptp = $r->activePromise;
        $amt = inr($ptp?->amount ?? $r->outstanding);
        $date = crm_date($ptp?->promise_date ?? $r->due_date);

        return 'Dear '.self::party($r).",\n\nAs per our conversation, we look forward to receiving {$amt} against Invoice No. {$r->invoice_no} by {$date}. Kindly confirm.\n\nRegards,\nTegh Cables India Pvt. Ltd.";
    }

    private static function statement(Invoice $r): string
    {
        return 'Dear '.self::party($r).",\n\nPlease find attached your account statement covering Invoice No. {$r->invoice_no} and other open invoices. Let us know if you need any clarification.\n\nRegards,\nTegh Cables India Pvt. Ltd.";
    }

    private static function confirmation(Invoice $r): string
    {
        return 'Dear '.self::party($r).",\n\nWe confirm receipt of payment against Invoice No. {$r->invoice_no}. Thank you for your prompt settlement.\n\nRegards,\nTegh Cables India Pvt. Ltd.";
    }

    private static function thankYou(Invoice $r): string
    {
        return 'Dear '.self::party($r).",\n\nThank you for clearing your outstanding dues with Tegh Cables India Pvt. Ltd. We value your continued business relationship.\n\nRegards,\nTegh Cables India Pvt. Ltd.";
    }
}
