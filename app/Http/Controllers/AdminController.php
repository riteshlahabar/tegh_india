<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Call;
use App\Models\EmailMessage;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\PaymentPromise;
use App\Models\User;
use App\Models\WhatsappMessage;
use App\Services\Admin\CustomerPageService;
use App\Services\Admin\OutstandingPageService;
use App\Services\Busy\BusyApiException;
use App\Services\Busy\BusySyncService;
use App\Services\WhatsApp\GreenApiClient;
use App\Services\WhatsApp\GreenApiException;
use App\Support\BusyCompanies;
use App\Support\SmtpSettings;
use App\Support\WhatsappTemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminController extends Controller
{
    public function invoiceJson(Invoice $invoice)
    {
        $invoice->load(['party', 'activePromise']);

        return response()->json($invoice->toActionArray());
    }

    public function partyDrawer(Party $party)
    {
        $data = app(CustomerPageService::class)->show($party->id);

        return view('partials.dashboard.party-drawer', $data);
    }

    public function storeCall(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'outcome' => 'required|in:'.implode(',', Call::OUTCOMES),
            'remarks' => 'nullable|string|max:5000',
            'next_followup_on' => 'nullable|date',
            'promise_amount' => 'nullable|numeric|min:0',
            'promise_date' => 'nullable|date',
        ]);

        $invoice = Invoice::with('party')->findOrFail($validated['invoice_id']);

        Call::create([
            'party_id' => $invoice->party_id,
            'invoice_id' => $invoice->id,
            'telecaller_id' => $request->user()->id,
            'called_at' => now(),
            'outcome' => $validated['outcome'],
            'remarks' => $validated['remarks'] ?: '(No remarks entered)',
            'next_followup_on' => $validated['next_followup_on'] ?? null,
            'promise_amount' => $validated['promise_amount'] ?? null,
            'promise_date' => $validated['promise_date'] ?? null,
        ]);

        $invoice->last_followup_at = now();
        if (! empty($validated['next_followup_on'])) {
            $invoice->next_followup_on = $validated['next_followup_on'];
        }

        if ($validated['outcome'] === 'Payment Promise') {
            PaymentPromise::where('invoice_id', $invoice->id)->whereNull('collected_at')->update([
                'status' => 'broken',
            ]);
            PaymentPromise::create([
                'party_id' => $invoice->party_id,
                'invoice_id' => $invoice->id,
                'telecaller_id' => $request->user()->id,
                'amount' => $validated['promise_amount'] ?? $invoice->outstanding,
                'promise_date' => $validated['promise_date'] ?? now()->addDays(2)->toDateString(),
                'status' => 'upcoming',
            ]);
        }

        $invoice->refreshStoredStatus();

        return $this->finish($request, 'Follow-up saved for '.$invoice->party?->name.'.');
    }

    public function storeWhatsapp(Request $request, GreenApiClient $greenApi)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'template' => 'nullable|string|max:100',
            'message' => 'required|string|max:5000',
        ]);

        $invoice = Invoice::with('party')->findOrFail($validated['invoice_id']);
        $mobile = (string) $invoice->party?->mobile;

        try {
            $messageId = $greenApi->sendText($mobile, $validated['message']);
        } catch (GreenApiException $e) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }

        WhatsappMessage::create([
            'party_id' => $invoice->party_id,
            'invoice_id' => $invoice->id,
            'user_id' => $request->user()->id,
            'mobile' => $mobile,
            'template' => $validated['template'] ?? 'Payment Reminder',
            'message' => $validated['message'],
            'sent_at' => now(),
        ]);

        $notice = 'WhatsApp sent to '.$invoice->party?->name.'.';

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'idMessage' => $messageId,
                'message' => $notice,
            ]);
        }

        return redirect()->back()->with('success', $notice);
    }

    public function updateWhatsappSettings(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'greenapi_api_url' => 'required|url|max:255',
            'greenapi_media_url' => 'required|url|max:255',
            'greenapi_id_instance' => 'required|string|max:64',
            'greenapi_api_token' => 'nullable|string|max:255',
            'greenapi_phone' => 'nullable|string|max:20',
        ]);

        AppSetting::setValue('greenapi_api_url', $validated['greenapi_api_url']);
        AppSetting::setValue('greenapi_media_url', $validated['greenapi_media_url']);
        AppSetting::setValue('greenapi_id_instance', $validated['greenapi_id_instance']);
        AppSetting::setValue('greenapi_phone', $validated['greenapi_phone'] ?? '');
        if (($validated['greenapi_api_token'] ?? '') !== '') {
            AppSetting::setValue('greenapi_api_token', $validated['greenapi_api_token']);
        }

        return redirect()->back()->with('success', 'WhatsApp connection saved.');
    }

    public function whatsappPreview(Request $request, Invoice $invoice)
    {
        $invoice->load(['party', 'activePromise']);
        $template = $request->query('template', 'Payment Reminder');

        return response()->json([
            'message' => WhatsappTemplates::render($template, $invoice),
            'invoice' => $invoice->toActionArray(),
        ]);
    }

    public function storeEmail(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'to_email' => 'required|email',
            'cc' => 'nullable|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:10000',
        ]);

        $invoice = Invoice::with('party')->findOrFail($validated['invoice_id']);

        EmailMessage::create([
            'party_id' => $invoice->party_id,
            'invoice_id' => $invoice->id,
            'user_id' => $request->user()->id,
            'to_email' => $validated['to_email'],
            'cc' => $validated['cc'] ?? null,
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'sent_at' => now(),
        ]);

        try {
            SmtpSettings::applyFromDatabase();
            Mail::raw($validated['body'], function ($message) use ($validated) {
                $message->to($validated['to_email'])
                    ->subject($validated['subject']);
                if (! empty($validated['cc'])) {
                    $message->cc($validated['cc']);
                }
            });
        } catch (\Throwable) {
            // CRM email log is the source of truth if SMTP delivery fails.
        }

        return $this->finish($request, 'Email reminder sent for '.$invoice->party?->name.'.');
    }

    public function assignParty(Request $request)
    {
        $validated = $request->validate([
            'method' => 'required|in:manual,round_robin',
            'party_id' => 'required|exists:parties,id',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $party = Party::findOrFail($validated['party_id']);
        $userId = $validated['user_id'] ?? null;

        if ($validated['method'] === 'round_robin') {
            $telecallers = User::where('role', 'telecaller')->orderBy('id')->pluck('id');
            if ($telecallers->isEmpty()) {
                return $this->finish($request, 'No telecallers available for round-robin.', false);
            }
            $index = Party::whereNotNull('assigned_to')->count() % $telecallers->count();
            $userId = $telecallers[$index];
        }

        if (! $userId) {
            return $this->finish($request, 'Select a user to assign.', false);
        }

        $party->update(['assigned_to' => $userId]);
        Invoice::where('party_id', $party->id)->update(['assigned_to' => $userId]);

        $user = User::find($userId);

        return $this->finish($request, $party->name.' assigned to '.$user?->name.'.');
    }

    private function finish(Request $request, string $message, bool $ok = true)
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'ok' => $ok,
                'message' => $message,
            ], $ok ? 200 : 422);
        }

        return redirect()->back()->with($ok ? 'success' : 'error', $message);
    }

    public function syncBusy(Request $request, BusySyncService $sync)
    {
        try {
            $result = $sync->sync($request->user()->username ?? 'web');
        } catch (BusyApiException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', sprintf(
            'BUSY sync completed: %d parties, %d invoices updated (%d closed).',
            $result['parties_upserted'],
            $result['invoices_upserted'],
            $result['invoices_closed'],
        ));
    }

    public function updateSettings(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'followup_reminder' => 'nullable|string|max:255',
            'smtp_enabled' => 'nullable|boolean',
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_encryption' => 'nullable|in:tls,ssl,none',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_from_address' => 'nullable|email|max:255',
            'smtp_from_name' => 'nullable|string|max:255',
            'busy_base_url' => 'nullable|url|max:255',
            'busy_credentials' => 'nullable|array',
            'busy_credentials.*.username' => 'nullable|string|max:255',
            'busy_credentials.*.password' => 'nullable|string|max:255',
        ]);

        if (array_key_exists('followup_reminder', $validated)) {
            AppSetting::setValue('followup_reminder', $validated['followup_reminder']);
        }

        if ($request->has('smtp_host') || $request->exists('smtp_enabled')) {
            AppSetting::setValue('smtp_enabled', $request->boolean('smtp_enabled') ? '1' : '0');
            AppSetting::setValue('smtp_host', $validated['smtp_host'] ?? '');
            AppSetting::setValue('smtp_port', (string) ($validated['smtp_port'] ?? 587));
            AppSetting::setValue('smtp_encryption', $validated['smtp_encryption'] ?? 'tls');
            AppSetting::setValue('smtp_username', $validated['smtp_username'] ?? '');
            if (array_key_exists('smtp_password', $validated) && ($validated['smtp_password'] ?? '') !== '') {
                AppSetting::setValue('smtp_password', $validated['smtp_password']);
            }
            AppSetting::setValue('smtp_from_address', $validated['smtp_from_address'] ?? '');
            AppSetting::setValue('smtp_from_name', $validated['smtp_from_name'] ?? '');
        }

        if ($request->has('busy_base_url')) {
            AppSetting::setValue('busy_base_url', rtrim((string) ($validated['busy_base_url'] ?? ''), '/'));
        }

        // One credential pair per Apna Bazar company -- the API has no company code,
        // so the login is what selects which company's books are returned. Unknown
        // slugs are ignored rather than stored.
        foreach (($validated['busy_credentials'] ?? []) as $slug => $credentials) {
            if (! BusyCompanies::exists((string) $slug)) {
                continue;
            }

            BusyCompanies::setCredentials(
                (string) $slug,
                $credentials['username'] ?? null,
                $credentials['password'] ?? null,
            );
        }

        return redirect()->back()->with('success', 'Settings saved.');
    }

    public function exportOutstanding(Request $request, OutstandingPageService $service)
    {
        $filters = $request->only(['party', 'invoice', 'sales', 'branch', 'assigned', 'status', 'aging', 'city']);
        $rows = $service->exportQuery($filters)->get();

        $filename = 'tegh-outstanding-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, [
                'Party', 'Code', 'Contact', 'Mobile', 'Email', 'City', 'Invoice No.', 'Narration',
                'Invoice Date', 'Due Date', 'Invoice Amt', 'Received', 'Outstanding',
                'Days Overdue', 'Sales Person', 'Assigned', 'Status',
            ]);
            foreach ($rows as $invoice) {
                fputcsv($out, [
                    $invoice->party?->name,
                    $invoice->party?->code,
                    $invoice->party?->contact_person,
                    $invoice->party?->mobile,
                    $invoice->party?->email,
                    $invoice->party?->city,
                    $invoice->invoice_no,
                    $invoice->narration ?: $invoice->po_no,
                    optional($invoice->invoice_date)->toDateString(),
                    optional($invoice->due_date)->toDateString(),
                    $invoice->invoice_amount,
                    $invoice->received_amount,
                    $invoice->outstanding,
                    $invoice->days_overdue > 0 ? $invoice->days_overdue : '',
                    $invoice->sales_person,
                    $invoice->assignee?->name,
                    $invoice->computed_status,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
