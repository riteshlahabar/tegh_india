<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CrmApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('api.token')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/meta', [CrmApiController::class, 'meta']);
    Route::get('/dashboard', [CrmApiController::class, 'dashboard']);
    Route::get('/outstanding', [CrmApiController::class, 'outstanding']);
    Route::get('/followups', [CrmApiController::class, 'followups']);
    Route::get('/customers', [CrmApiController::class, 'customers']);
    Route::get('/customers/{party}', [CrmApiController::class, 'customer']);
    Route::post('/customers/{party}/mobile', [CrmApiController::class, 'updateMobile']);
    Route::get('/invoices/{invoice}', [CrmApiController::class, 'invoice']);
    Route::get('/ptp', [CrmApiController::class, 'ptp']);
    Route::get('/calls', [CrmApiController::class, 'calls']);
    Route::post('/calls', [CrmApiController::class, 'storeCall']);
    Route::get('/whatsapp', [CrmApiController::class, 'whatsapp']);
    Route::post('/whatsapp', [CrmApiController::class, 'storeWhatsapp']);
    Route::get('/invoices/{invoice}/whatsapp-preview', [CrmApiController::class, 'whatsappPreview']);
    Route::get('/email', [CrmApiController::class, 'email']);
    Route::post('/email', [CrmApiController::class, 'storeEmail']);
    Route::get('/reports', [CrmApiController::class, 'reports']);
    Route::get('/team', [CrmApiController::class, 'team']);
    Route::post('/assign', [CrmApiController::class, 'assign']);
});
