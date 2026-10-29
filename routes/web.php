<?php

use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\Admin\TelecallerController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BusyApiController;
use App\Http\Controllers\StorageLinkController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

Route::get('/storage-link', StorageLinkController::class)->name('storage.link');

Route::middleware(['auth', 'crm'])->group(function () {
    Route::get('/', [AdminPageController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/outstanding', [AdminPageController::class, 'outstanding'])->name('admin.outstanding');
    Route::get('/admin/outstanding/export', [AdminController::class, 'exportOutstanding'])->name('admin.outstanding.export');
    Route::get('/admin/followups', [AdminPageController::class, 'followups'])->name('admin.followups');
    Route::get('/admin/customers', [AdminPageController::class, 'customers'])->name('admin.customers');
    Route::get('/admin/parties/{party}/drawer', [AdminController::class, 'partyDrawer'])->name('admin.parties.drawer');
    Route::get('/admin/invoices/{invoice}', [AdminController::class, 'invoiceJson'])->name('admin.invoices.show');
    Route::get('/admin/invoices/{invoice}/whatsapp-preview', [AdminController::class, 'whatsappPreview'])->name('admin.invoices.wa-preview');
    Route::get('/admin/ptp', [AdminPageController::class, 'ptp'])->name('admin.ptp');
    Route::get('/admin/calls', [AdminPageController::class, 'calls'])->name('admin.calls');
    Route::get('/admin/whatsapp', [AdminPageController::class, 'whatsapp'])->name('admin.whatsapp');
    Route::get('/admin/email', [AdminPageController::class, 'email'])->name('admin.email');
    Route::get('/admin/reports', [AdminPageController::class, 'reports'])->name('admin.reports');
    Route::get('/admin/users', [AdminPageController::class, 'users'])->name('admin.users');
    Route::post('/admin/telecallers', [TelecallerController::class, 'store'])->name('admin.telecallers.store');
    Route::put('/admin/telecallers/{user}', [TelecallerController::class, 'update'])->name('admin.telecallers.update');
    Route::delete('/admin/telecallers/{user}', [TelecallerController::class, 'destroy'])->name('admin.telecallers.destroy');
    Route::get('/admin/busy', [AdminPageController::class, 'busyApi'])->name('admin.busy.api');
    Route::get('/admin/settings', [AdminPageController::class, 'settings'])->name('admin.settings');

    Route::post('/admin/calls', [AdminController::class, 'storeCall'])->name('admin.calls.store');
    Route::post('/admin/whatsapp', [AdminController::class, 'storeWhatsapp'])->name('admin.whatsapp.store');
    Route::post('/admin/whatsapp/settings', [AdminController::class, 'updateWhatsappSettings'])->name('admin.whatsapp.settings');
    Route::post('/admin/email', [AdminController::class, 'storeEmail'])->name('admin.email.store');
    Route::post('/admin/assign', [AdminController::class, 'assignParty'])->name('admin.assign');
    Route::post('/admin/busy-sync', [AdminController::class, 'syncBusy'])->name('admin.busy.sync');
    Route::get('/admin/busy/api', [BusyApiController::class, 'catalog'])->name('admin.busy.api.catalog');
    Route::post('/admin/busy/api/{action}', [BusyApiController::class, 'call'])->name('admin.busy.api.call');
    Route::post('/admin/settings', [AdminController::class, 'updateSettings'])->name('admin.settings.update');
});
