<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\User;
use App\Services\Admin\CallHistoryPageService;
use App\Services\Admin\CustomerPageService;
use App\Services\Admin\DashboardPageService;
use App\Services\Admin\EmailPageService;
use App\Services\Admin\FollowupPageService;
use App\Services\Admin\OutstandingPageService;
use App\Services\Admin\PtpPageService;
use App\Services\Admin\ReportPageService;
use App\Services\Admin\StaffPageService;
use App\Services\Admin\WhatsappPageService;
use App\Services\WhatsApp\GreenApiClient;
use App\Support\CrmPayload;
use App\Support\WhatsappTemplates;
use Illuminate\Http\Request;

class CrmApiController extends Controller
{
    public function meta()
    {
        return response()->json([
            'outcomes' => Call::OUTCOMES,
            'templates' => WhatsappTemplates::names(),
            'statuses' => Invoice::STATUSES,
            'aging' => Invoice::AGING_BUCKETS,
            'telecallers' => User::where('role', 'telecaller')->orderBy('name')->get()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])->values(),
            'sales_persons' => Invoice::query()->whereNotNull('sales_person')->distinct()->orderBy('sales_person')->pluck('sales_person')->values(),
            'branches' => Invoice::query()->whereNotNull('branch')->distinct()->orderBy('branch')->pluck('branch')->values(),
            'cities' => \App\Services\Admin\OutstandingPageService::CITIES,
        ]);
    }

    public function dashboard(DashboardPageService $service)
    {
        $data = $service->data();
        $kpis = $data['kpis'];

        return response()->json([
            'kpis' => [
                'total_outstanding' => (float) $kpis['totalOutstanding'],
                'total_outstanding_inr' => inr($kpis['totalOutstanding']),
                'open_invoices' => (int) $kpis['openInvoices'],
                'overdue' => (float) $kpis['overdue'],
                'overdue_inr' => inr($kpis['overdue']),
                'due_today' => (float) $kpis['dueToday'],
                'due_today_inr' => inr($kpis['dueToday']),
                'due_today_count' => (int) $kpis['dueTodayCount'],
                'ptp_amount' => (float) $kpis['ptpAmount'],
                'ptp_amount_inr' => inr($kpis['ptpAmount']),
                'ptp_count' => (int) $kpis['ptpCount'],
                'collected_this_month' => (float) $kpis['collectedThisMonth'],
                'collected_this_month_inr' => inr($kpis['collectedThisMonth']),
                'followups_today' => (int) $kpis['followupsToday'],
                'completed_followups' => (int) $kpis['completedFollowups'],
            ],
            'aging' => collect($data['agingLabels'])->values()->map(fn ($label, $index) => [
                'label' => $label,
                'amount' => (float) $data['agingValues'][$index],
                'amount_inr' => inr($data['agingValues'][$index]),
            ])->values(),
            'followups' => collect($data['todayFollowups'])->map(fn (Invoice $invoice) => CrmPayload::invoice($invoice))->values(),
            'team' => collect($data['teamSnapshot'])->map(fn ($row) => [
                'name' => $row['name'],
                'initials' => $row['initials'],
                'count' => (int) $row['count'],
                'amount' => (float) $row['amount'],
                'amount_inr' => inr($row['amount']),
            ])->values(),
        ]);
    }

    public function outstanding(Request $request, OutstandingPageService $service)
    {
        $filters = $request->only(['party', 'invoice', 'sales', 'branch', 'assigned', 'status', 'aging', 'city', 'company']);
        $data = $service->data($filters);

        return response()->json(array_merge(
            CrmPayload::page($data['invoices'], fn (Invoice $invoice) => CrmPayload::invoice($invoice)),
            [
                'total_count' => $data['totalCount'],
                'filtered_count' => $data['filteredCount'],
            ]
        ));
    }

    public function followups(Request $request, FollowupPageService $service)
    {
        $data = $service->data($request->query('q'));

        return response()->json([
            'today_label' => $data['todayLabel'],
            'data' => collect($data['invoices'])->map(fn (Invoice $invoice) => CrmPayload::invoice($invoice))->values(),
        ]);
    }

    public function customers(Request $request, CustomerPageService $service)
    {
        $data = $service->data($request->query('q'));

        return response()->json([
            'data' => collect($data['parties'])->map(fn ($party) => CrmPayload::party($party))->values(),
        ]);
    }

    public function customer(int $party, CustomerPageService $service)
    {
        $data = $service->show($party);

        return response()->json([
            'party' => CrmPayload::party($data['party']),
            'invoices' => collect($data['invoices'])->map(fn (Invoice $invoice) => CrmPayload::invoice($invoice))->values(),
            'calls' => collect($data['calls'])->map(fn (Call $call) => CrmPayload::call($call))->values(),
        ]);
    }

    public function updateMobile(Request $request, int $party)
    {
        $validated = $request->validate([
            'mobile' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s]{8,30}$/'],
        ]);

        $model = Party::query()->findOrFail($party);
        $model->mobile = trim($validated['mobile']);
        $model->save();

        return response()->json([
            'ok' => true,
            'mobile' => $model->mobile,
        ]);
    }

    public function invoice(Invoice $invoice)
    {
        return response()->json([
            'invoice' => CrmPayload::invoice($invoice),
        ]);
    }

    public function ptp(PtpPageService $service)
    {
        $data = $service->data();

        return response()->json([
            'total' => (float) $data['ptpTotal'],
            'total_inr' => inr($data['ptpTotal']),
            'due_today' => (int) $data['ptpDueToday'],
            'overdue' => (int) $data['ptpOverdue'],
            'collected' => (float) $data['ptpCollected'],
            'collected_inr' => inr($data['ptpCollected']),
            'data' => collect($data['promises'])->map(fn ($promise) => CrmPayload::promise($promise))->values(),
        ]);
    }

    public function calls(Request $request, CallHistoryPageService $service)
    {
        $data = $service->data($request->only(['date', 'telecaller', 'outcome', 'party']));

        return response()->json(
            CrmPayload::page($data['calls'], fn (Call $call) => CrmPayload::call($call))
        );
    }

    public function storeCall(Request $request, AdminController $admin)
    {
        $request->headers->set('Accept', 'application/json');

        return $admin->storeCall($request);
    }

    public function whatsapp(WhatsappPageService $service)
    {
        $data = $service->data();

        return response()->json(array_merge(
            CrmPayload::page($data['messages'], fn ($message) => CrmPayload::whatsapp($message)),
            ['connected' => (bool) $data['hasGreenToken']]
        ));
    }

    public function storeWhatsapp(Request $request, AdminController $admin)
    {
        $request->headers->set('Accept', 'application/json');

        return $admin->storeWhatsapp($request, app(GreenApiClient::class));
    }

    public function whatsappPreview(Request $request, Invoice $invoice, AdminController $admin)
    {
        $request->headers->set('Accept', 'application/json');

        return $admin->whatsappPreview($request, $invoice);
    }

    public function email(EmailPageService $service)
    {
        $data = $service->data();

        return response()->json(
            CrmPayload::page($data['messages'], fn ($message) => CrmPayload::email($message))
        );
    }

    public function storeEmail(Request $request, AdminController $admin)
    {
        $request->headers->set('Accept', 'application/json');

        return $admin->storeEmail($request);
    }

    public function reports(Request $request, ReportPageService $service)
    {
        $data = $service->data($request->only([
            'type', 'from', 'to', 'party_id', 'sales', 'telecaller', 'branch', 'status',
        ]));

        return response()->json([
            'reports' => collect($data['reports'])->map(fn ($report) => [
                'key' => $report['key'],
                'name' => $report['name'],
                'description' => $report['desc'],
            ])->values(),
            'type' => $data['type'],
            'title' => $data['resultTitle'],
            'data' => collect($data['resultRows'])->map(fn ($row) => CrmPayload::reportRow($row))->values(),
        ]);
    }

    public function team(StaffPageService $service)
    {
        $data = $service->data();

        return response()->json([
            'data' => collect($data['team'])->map(function ($row) {
                $user = $row['user'];

                return [
                    'user' => CrmPayload::user($user),
                    'assigned' => (int) $row['assigned'],
                    'followups' => (int) $row['followups'],
                    'calls' => (int) $row['calls'],
                    'calls_today' => (int) $row['callsToday'],
                    'connected' => (int) $row['connected'],
                    'ptp' => (int) $row['ptp'],
                    'collected' => (float) $row['collected'],
                    'collected_inr' => inr($row['collected']),
                ];
            })->values(),
        ]);
    }

    public function assign(Request $request, AdminController $admin)
    {
        $request->headers->set('Accept', 'application/json');

        return $admin->assignParty($request);
    }
}
