<?php

use App\Http\Controllers\Admin\CarPool\AdminCarPoolController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CarPool Admin Routes  — /admin/carpool/*
|--------------------------------------------------------------------------
*/

Route::prefix('carpool')->middleware(['web', 'admin'])->group(function () {

    // ── Drivers ────────────────────────────────────────────────────────────
    Route::prefix('drivers')->group(function () {
        Route::get('/',               [AdminCarPoolController::class, 'drivers']);
        Route::put('{id}/verify',     [AdminCarPoolController::class, 'verifyDriver']);
        Route::put('{id}/status',     [AdminCarPoolController::class, 'updateDriverStatus']);
    });

    // ── Routes ─────────────────────────────────────────────────────────────
    Route::get('routes',              [AdminCarPoolController::class, 'routes']);

    // ── Bookings ───────────────────────────────────────────────────────────
    Route::get('bookings',            [AdminCarPoolController::class, 'bookings']);

    // ── Reports ────────────────────────────────────────────────────────────
    Route::get('commission-report',   [AdminCarPoolController::class, 'commissionReport']);

    // ── Withdrawals ────────────────────────────────────────────────────────
    Route::prefix('withdrawals')->group(function () {
        Route::get('/',               [AdminCarPoolController::class, 'withdrawalRequests']);
        Route::put('{id}/approve',    [AdminCarPoolController::class, 'approveWithdrawal']);
        Route::put('{id}/reject',     [AdminCarPoolController::class, 'rejectWithdrawal']);
    });
});
