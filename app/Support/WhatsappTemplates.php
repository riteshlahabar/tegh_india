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
            'Payment Reminder' => fn (Invoice $r) => self::paymentReminder($r),
            'Due Today' => fn (Invoice $r) => self::dueToday($r),
            'Overdue Payment' => fn (Invoice $r) => self::overdue($r),
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
     * The three reminder templates below quote the customer's TOTAL pending
     * dues -- the same figure the party drawer shows -- not the one invoice the
     * WhatsApp button happened to be wired to. When the party has exactly one
     * pending invoice the two are the same number, and the older wording that
     * names that invoice is kept because it is more useful to the customer.
     */
    private static function paymentReminder(Invoice $r): string
    {
        $dues = PartyDues::forInvoice($r);

        $subject = $dues->spansMultipleInvoices()
            ? $dues->totalInr().' against '.$dues->invoiceLabel().', the oldest of which was due on '.crm_date($dues->oldestDueDate)
            : inr($r->outstanding)." against Invoice No. {$r->invoice_no}, which was due on ".crm_date($r->due_date);

        return 'Dear '.self::party($r).",\n\nThis is a gentle reminder regarding the outstanding payment of ".$subject.".\n\nKindly arrange the payment at the earliest.\n\nRegards,\nTegh Cables India Pvt. Ltd.";
    }

    private static function dueToday(Invoice $r): string
    {
        $dues = PartyDues::forInvoice($r);

        // The party total covers older bills too, so it is never described as
        // "due today" on its own -- the invoice that IS due today is named
        // beside it.
        $body = $dues->spansMultipleInvoices()
            ? "Invoice No. {$r->invoice_no} for ".inr($r->outstanding).' is due today ('.crm_date($r->due_date).'), taking your total outstanding to '.$dues->totalInr().' across '.$dues->invoiceLabel().'. Kindly process the payment today to avoid any inconvenience.'
            : 'Your payment of '.inr($r->outstanding)." against Invoice No. {$r->invoice_no} is due today (".crm_date($r->due_date).'). Kindly process the payment today to avoid any inconvenience.';

        return 'Dear '.self::party($r).",\n\n".$body."\n\nRegards,\nTegh Cables India Pvt. Ltd.";
    }

    private static function overdue(Invoice $r): string
    {
        $dues = PartyDues::forInvoice($r);

        $body = $dues->spansMultipleInvoices()
            ? 'Our records show an overdue amount of '.$dues->totalInr().' against '.$dues->invoiceLabel().', the oldest of which was due on '.crm_date($dues->oldestDueDate).'. Please arrange payment immediately to avoid disruption in supply.'
            : "Our records show Invoice No. {$r->invoice_no} for ".inr($r->outstanding).' is now overdue (due date '.crm_date($r->due_date).'). Please arrange payment immediately to avoid disruption in supply.';

        return 'Dear '.self::party($r).",\n\n".$body."\n\nRegards,\nTegh Cables India Pvt. Ltd.";
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
