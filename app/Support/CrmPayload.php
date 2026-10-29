<?php

namespace App\Support;

use App\Models\Call;
use App\Models\EmailMessage;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\PaymentPromise;
use App\Models\User;
use App\Models\WhatsappMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CrmPayload
{
    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'role_label' => $user->isAdmin() ? 'Administrator' : 'Payment Follow-up Executive',
            'initials' => $user->initials ?: crm_initials($user->name),
            'branch' => $user->branch,
        ];
    }

    public static function invoice(Invoice $invoice): array
    {
        $invoice->loadMissing(['party', 'assignee', 'activePromise']);

        return [
            'id' => $invoice->id,
            'invoice_no' => $invoice->invoice_no,
            'narration' => $invoice->narration ?: $invoice->po_no,
            'po_no' => $invoice->po_no,
            'party_id' => $invoice->party_id,
            'party' => $invoice->party?->name,
            'code' => $invoice->party?->code,
            'contact' => $invoice->party?->contact_person,
            'mobile' => $invoice->party?->mobile,
            'email' => $invoice->party?->email,
            'city' => $invoice->party?->city,
            'state' => $invoice->party?->state,
            'outstanding' => (float) $invoice->outstanding,
            'outstanding_inr' => inr($invoice->outstanding),
            'invoice_amount' => (float) $invoice->invoice_amount,
            'invoice_amount_inr' => inr($invoice->invoice_amount),
            'received_amount' => (float) $invoice->received_amount,
            'received_inr' => inr($invoice->received_amount),
            'invoice_date' => optional($invoice->invoice_date)->toDateString(),
            'invoice_date_label' => crm_date($invoice->invoice_date),
            'due_date' => optional($invoice->due_date)->toDateString(),
            'due_date_label' => crm_date($invoice->due_date),
            'status' => $invoice->computed_status,
            'priority' => $invoice->priority,
            'days_overdue' => $invoice->days_overdue,
            'aging_bucket' => $invoice->aging_bucket,
            'sales_person' => $invoice->sales_person,
            'branch' => $invoice->branch,
            'assigned' => $invoice->assignee?->name,
            'assigned_to' => $invoice->assigned_to,
            'next_followup_on' => optional($invoice->next_followup_on)->toDateString(),
            'next_followup_label' => crm_date($invoice->next_followup_on),
            'promise_amount' => $invoice->activePromise?->amount,
            'promise_date' => optional($invoice->activePromise?->promise_date)->toDateString(),
        ];
    }

    public static function party(Party $party): array
    {
        $outstanding = $party->getAttribute('total_outstanding');
        if ($outstanding === null) {
            $outstanding = $party->relationLoaded('invoices')
                ? $party->invoices->sum('outstanding')
                : $party->totalOutstanding();
        }

        $count = $party->getAttribute('invoice_count');
        if ($count === null) {
            $count = $party->relationLoaded('invoices')
                ? $party->invoices->count()
                : $party->invoices()->count();
        }

        $status = $party->getAttribute('rollup_status') ?: $party->rollupStatus();
        $callInvoice = self::callInvoice($party);

        return [
            'id' => $party->id,
            'name' => $party->name,
            'code' => $party->code,
            'contact' => $party->contact_person,
            'mobile' => $party->mobile,
            'email' => $party->email,
            'gstin' => $party->gstin,
            'city' => $party->city,
            'state' => $party->state,
            'sales_person' => $party->sales_person,
            'branch' => $party->branch,
            'assigned' => $party->assignee?->name,
            'assigned_to' => $party->assigned_to,
            'credit_limit' => (float) $party->credit_limit,
            'credit_limit_inr' => inr($party->credit_limit),
            'outstanding' => (float) $outstanding,
            'outstanding_inr' => inr($outstanding),
            'invoice_count' => (int) $count,
            'status' => $status,
            'call_invoice_id' => $callInvoice?->id,
            'call_invoice_no' => $callInvoice?->invoice_no,
        ];
    }

    private static function callInvoice(Party $party): ?Invoice
    {
        $invoices = $party->relationLoaded('invoices')
            ? $party->invoices
            : $party->invoices()->get();

        return $invoices
            ->filter(fn (Invoice $invoice) => (float) $invoice->outstanding > 0)
            ->sortByDesc(fn (Invoice $invoice) => sprintf('%d-%012.2f', $invoice->days_overdue > 0 ? 1 : 0, $invoice->outstanding))
            ->first();
    }

    public static function call(Call $call): array
    {
        return [
            'id' => $call->id,
            'party_id' => $call->party_id,
            'party' => $call->party?->name,
            'invoice_id' => $call->invoice_id,
            'invoice_no' => $call->invoice?->invoice_no,
            'outcome' => $call->outcome,
            'remarks' => $call->remarks,
            'telecaller' => $call->telecaller?->name,
            'called_at' => optional($call->called_at)->format('d M Y H:i'),
            'next_followup_on' => optional($call->next_followup_on)->toDateString(),
            'promise_amount' => $call->promise_amount,
            'promise_amount_inr' => $call->promise_amount !== null ? inr($call->promise_amount) : null,
            'promise_date' => optional($call->promise_date)->toDateString(),
        ];
    }

    public static function promise(PaymentPromise $promise): array
    {
        return [
            'id' => $promise->id,
            'party' => $promise->party?->name,
            'party_id' => $promise->party_id,
            'invoice_id' => $promise->invoice_id,
            'invoice_no' => $promise->invoice?->invoice_no,
            'mobile' => $promise->party?->mobile,
            'contact' => $promise->party?->contact_person,
            'amount' => (float) $promise->amount,
            'amount_inr' => inr($promise->amount),
            'promise_date' => optional($promise->promise_date)->toDateString(),
            'promise_date_label' => crm_date($promise->promise_date),
            'status' => $promise->display_status,
            'telecaller' => $promise->telecaller?->name,
        ];
    }

    public static function whatsapp(WhatsappMessage $message): array
    {
        return [
            'id' => $message->id,
            'party' => $message->party?->name,
            'invoice_no' => $message->invoice?->invoice_no,
            'mobile' => $message->mobile,
            'template' => $message->template,
            'message' => $message->message,
            'user' => $message->user?->name,
            'sent_at' => optional($message->sent_at)->format('d M Y H:i'),
        ];
    }

    public static function email(EmailMessage $message): array
    {
        return [
            'id' => $message->id,
            'party' => $message->party?->name,
            'invoice_no' => $message->invoice?->invoice_no,
            'to_email' => $message->to_email,
            'cc' => $message->cc,
            'subject' => $message->subject,
            'body' => $message->body,
            'user' => $message->user?->name,
            'sent_at' => optional($message->sent_at)->format('d M Y H:i'),
        ];
    }

    public static function page(LengthAwarePaginator $paginator, callable $map): array
    {
        return [
            'data' => collect($paginator->items())->map($map)->values(),
            'page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ];
    }

    public static function reportRow(mixed $row): mixed
    {
        if ($row instanceof Invoice) {
            return self::invoice($row);
        }

        if (! is_array($row)) {
            return $row;
        }

        if (isset($row['amount'])) {
            $row['amount'] = (float) $row['amount'];
            $row['amount_inr'] = inr($row['amount']);
        }
        if (isset($row['collected'])) {
            $row['collected'] = (float) $row['collected'];
            $row['collected_inr'] = inr($row['collected']);
        }
        if (isset($row['target'])) {
            $row['target'] = (float) $row['target'];
            $row['target_inr'] = inr($row['target']);
        }

        return $row;
    }
}
