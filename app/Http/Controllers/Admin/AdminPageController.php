<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminSharedDataService;
use App\Services\Admin\BusyApiPageService;
use App\Services\Admin\CallHistoryPageService;
use App\Services\Admin\CustomerPageService;
use App\Services\Admin\DashboardPageService;
use App\Services\Admin\EmailPageService;
use App\Services\Admin\FollowupPageService;
use App\Services\Admin\OutstandingPageService;
use App\Services\Admin\PtpPageService;
use App\Services\Admin\ReportPageService;
use App\Services\Admin\SettingsPageService;
use App\Services\Admin\StaffPageService;
use App\Services\Admin\WhatsappPageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * One controller per admin screen — each page loads only its own service data.
 */
class AdminPageController extends Controller
{
    public function __construct(
        private AdminSharedDataService $shared
    ) {}

    public function dashboard(DashboardPageService $service): View
    {
        return $this->render('admin.pages.dashboard', $service->data(), [
            'activeNav' => 'dashboard',
            'pageTitle' => 'Dashboard',
            'pageSubtitle' => 'Overview of outstanding & collections',
            'docTitle' => 'Dashboard | Tegh Cables CRM',
        ]);
    }

    public function outstanding(Request $request, OutstandingPageService $service): View
    {
        return $this->render('admin.pages.outstanding', $service->data($request->only([
            'party', 'invoice', 'sales', 'branch', 'assigned', 'status', 'aging', 'city', 'company',
        ])), [
            'activeNav' => 'outstanding',
            'pageTitle' => 'Outstanding',
            'pageSubtitle' => 'All open and part-paid invoices',
            'docTitle' => 'Outstanding | Tegh Cables CRM',
        ]);
    }

    public function followups(Request $request, FollowupPageService $service): View
    {
        return $this->render('admin.pages.followups', $service->data($request->query('q')), [
            'activeNav' => 'followups',
            'pageTitle' => "Today's Follow-ups",
            'pageSubtitle' => 'Accounts due for calling today',
            'docTitle' => "Today's Follow-ups | Tegh Cables CRM",
        ]);
    }

    public function customers(Request $request, CustomerPageService $service): View
    {
        return $this->render('admin.pages.customers', $service->data($request->query('q')), [
            'activeNav' => 'customers',
            'pageTitle' => 'Customers / Parties',
            'pageSubtitle' => 'Master list of all Tegh Cables accounts',
            'docTitle' => 'Customers | Tegh Cables CRM',
        ]);
    }

    public function ptp(PtpPageService $service): View
    {
        return $this->render('admin.pages.ptp', $service->data(), [
            'activeNav' => 'ptp',
            'pageTitle' => 'Payment Promises',
            'pageSubtitle' => 'Track PTP commitments and statuses',
            'docTitle' => 'Payment Promises | Tegh Cables CRM',
        ]);
    }

    public function calls(Request $request, CallHistoryPageService $service): View
    {
        return $this->render('admin.pages.calls', $service->data($request->only([
            'date', 'telecaller', 'outcome', 'party',
        ])), [
            'activeNav' => 'callhistory',
            'pageTitle' => 'Call History',
            'pageSubtitle' => 'Log of all telecaller follow-up calls',
            'docTitle' => 'Call History | Tegh Cables CRM',
        ]);
    }

    public function whatsapp(WhatsappPageService $service): View
    {
        return $this->render('admin.pages.whatsapp', $service->data(), [
            'activeNav' => 'whatsapp',
            'pageTitle' => 'WhatsApp',
            'pageSubtitle' => 'WhatsApp reminder log',
            'docTitle' => 'WhatsApp | Tegh Cables CRM',
        ]);
    }

    public function email(EmailPageService $service): View
    {
        return $this->render('admin.pages.email', $service->data(), [
            'activeNav' => 'email',
            'pageTitle' => 'Email',
            'pageSubtitle' => 'Email reminder log',
            'docTitle' => 'Email | Tegh Cables CRM',
        ]);
    }

    public function reports(Request $request, ReportPageService $service): View
    {
        return $this->render('admin.pages.reports', $service->data($request->only([
            'type', 'from', 'to', 'party_id', 'sales', 'telecaller', 'branch', 'status',
        ])), [
            'activeNav' => 'reports',
            'pageTitle' => 'Reports',
            'pageSubtitle' => 'Outstanding, aging and performance reports',
            'docTitle' => 'Reports | Tegh Cables CRM',
        ]);
    }

    public function users(StaffPageService $service): View
    {
        return $this->render('admin.pages.users', $service->data(), [
            'activeNav' => 'users',
            'pageTitle' => 'Users / Team',
            'pageSubtitle' => 'Manage telecallers and assignments',
            'docTitle' => 'Users | Tegh Cables CRM',
        ]);
    }

    public function busyApi(BusyApiPageService $service): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return $this->render('admin.pages.busy-api', $service->data(), [
            'activeNav' => 'busy-api',
            'pageTitle' => 'BUSY API',
            'pageSubtitle' => 'Apna Bazar Api Bundle V2',
            'docTitle' => 'BUSY API | Tegh Cables CRM',
        ]);
    }

    public function settings(SettingsPageService $service): View
    {
        return $this->render('admin.pages.settings', $service->data(), [
            'activeNav' => 'settings',
            'pageTitle' => 'Settings',
            'pageSubtitle' => 'Company profile and data source configuration',
            'docTitle' => 'Settings | Tegh Cables CRM',
        ]);
    }

    private function render(string $view, array $pageData, array $meta): View
    {
        return view($view, array_merge(
            $this->shared->forLayout(),
            $pageData,
            $meta
        ));
    }
}
