<?php

namespace App\Services\Admin;

use App\Models\AppSetting;
use App\Models\Call;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\PaymentPromise;
use App\Models\User;
use App\Support\WhatsappTemplates;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class AdminSharedDataService
{
    public function forLayout(): array
    {
        $today = Carbon::today();
        $overdueFollowups = 0;
        $ptpToday = 0;
        $openInvoices = 0;
        $salesPersons = collect();
        $branches = collect();

        if (Schema::hasTable('invoices')) {
            $overdueFollowups = Invoice::where('outstanding', '>', 0)
                ->whereDate('due_date', '<', $today)
                ->count();
            $openInvoices = Invoice::where('outstanding', '>', 0)->count();
            $salesPersons = Invoice::query()->whereNotNull('sales_person')->distinct()->orderBy('sales_person')->pluck('sales_person');
            $branches = Invoice::query()->whereNotNull('branch')->distinct()->orderBy('branch')->pluck('branch');
        }

        if (Schema::hasTable('payment_promises')) {
            $ptpToday = PaymentPromise::whereNull('collected_at')
                ->whereDate('promise_date', $today)
                ->count();
        }

        $notifications = [
            [
                'tone' => 'red',
                'label' => $overdueFollowups.' overdue follow-ups pending',
                'route' => 'admin.ptp',
            ],
            [
                'tone' => 'orange',
                'label' => $ptpToday.' payment promises due today',
                'route' => 'admin.ptp',
            ],
            [
                'tone' => 'blue',
                'label' => $openInvoices.' open outstanding invoices',
                'route' => 'admin.outstanding',
            ],
        ];

        return [
            'navItems' => $this->navItems(),
            'telecallers' => Schema::hasTable('users')
                ? User::where('role', 'telecaller')->orderBy('name')->get()
                : collect(),
            'salesPersons' => $salesPersons,
            'branches' => $branches,
            'cities' => OutstandingPageService::CITIES,
            'callOutcomes' => Call::OUTCOMES,
            'waTemplates' => WhatsappTemplates::names(),
            'notifications' => $notifications,
            'notificationCount' => count($notifications),
            'lastBusySync' => AppSetting::getValue('busy_last_sync'),
            'clockLabel' => now(config('app.timezone', 'Asia/Kolkata'))->format('D, d M · h:i A'),
            'parties' => Schema::hasTable('parties')
                ? Party::orderBy('name')->get(['id', 'name'])
                : collect(),
        ];
    }

    public function navItems(): array
    {
        $items = [
            ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-solid fa-gauge-high', 'route' => 'admin.dashboard'],
            ['id' => 'outstanding', 'label' => 'Outstanding', 'icon' => 'fa-solid fa-file-invoice-dollar', 'route' => 'admin.outstanding'],
            ['id' => 'followups', 'label' => "Today's Follow-ups", 'icon' => 'fa-solid fa-list-check', 'route' => 'admin.followups'],
            ['id' => 'customers', 'label' => 'Customers / Parties', 'icon' => 'fa-solid fa-building', 'route' => 'admin.customers'],
            ['id' => 'ptp', 'label' => 'Payment Promises', 'icon' => 'fa-solid fa-handshake', 'route' => 'admin.ptp'],
            ['id' => 'callhistory', 'label' => 'Call History', 'icon' => 'fa-solid fa-phone', 'route' => 'admin.calls'],
            ['id' => 'whatsapp', 'label' => 'WhatsApp', 'icon' => 'fa-brands fa-whatsapp', 'route' => 'admin.whatsapp'],
            ['id' => 'email', 'label' => 'Email', 'icon' => 'fa-regular fa-envelope', 'route' => 'admin.email'],
            ['id' => 'reports', 'label' => 'Reports', 'icon' => 'fa-solid fa-chart-column', 'route' => 'admin.reports'],
            ['id' => 'users', 'label' => 'Users / Team', 'icon' => 'fa-solid fa-users', 'route' => 'admin.users'],
        ];

        if (auth()->user()?->isAdmin()) {
            $items[] = ['id' => 'busy-api', 'label' => 'BUSY API', 'icon' => 'fa-solid fa-plug', 'route' => 'admin.busy.api'];
        }

        $items[] = ['id' => 'settings', 'label' => 'Settings', 'icon' => 'fa-solid fa-gear', 'route' => 'admin.settings'];

        return $items;
    }
}
