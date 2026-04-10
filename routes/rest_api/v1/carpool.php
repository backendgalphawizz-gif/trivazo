<?php

use App\Http\Controllers\RestAPI\v1\CarPool\DriverAuthController;
use App\Http\Controllers\RestAPI\v1\CarPool\DriverRouteController;
use App\Http\Controllers\RestAPI\v1\CarPool\DriverWalletController;
use App\Http\Controllers\RestAPI\v1\CarPool\PassengerBookingController;
use App\Http\Controllers\RestAPI\v1\CarPool\PassengerRouteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CarPool REST API Routes  — /api/v1/carpool/*
|--------------------------------------------------------------------------
*/

Route::prefix('v1/carpool')->group(function () {

    // ── Driver auth (public) ───────────────────────────────────────────────
    Route::prefix('driver')->group(function () {
        Route::post('register', [DriverAuthController::class, 'register']);
        Route::post('login',    [DriverAuthController::class, 'login']);
    });

    // ── Passenger: route search (public) ──────────────────────────────────
    Route::prefix('routes')->group(function () {
        Route::get('search', [PassengerRouteController::class, 'search']);
        Route::get('{id}',   [PassengerRouteController::class, 'show']);
    });

    // ── Driver authenticated endpoints ────────────────────────────────────
    Route::prefix('driver')->middleware('auth:carpool_driver')->group(function () {
        Route::post('logout',         [DriverAuthController::class, 'logout']);
        Route::get('profile',         [DriverAuthController::class, 'profile']);
        Route::put('profile',         [DriverAuthController::class, 'updateProfile']);

        // Driver wallet
        Route::prefix('wallet')->group(function () {
            Route::get('/',           [DriverWalletController::class, 'wallet']);
            Route::get('transactions',[DriverWalletController::class, 'transactions']);
            Route::post('withdraw',   [DriverWalletController::class, 'requestWithdrawal']);
            Route::get('withdrawals', [DriverWalletController::class, 'withdrawals']);
        });

        // Driver ride routes management
        Route::prefix('my-routes')->group(function () {
            Route::post('/',             [DriverRouteController::class, 'store']);
            Route::get('/',              [DriverRouteController::class, 'myRoutes']);
            Route::get('{id}',           [DriverRouteController::class, 'show']);
            Route::post('{id}/depart',   [DriverRouteController::class, 'depart']);
            Route::post('{id}/complete', [DriverRouteController::class, 'complete']);
            Route::delete('{id}',        [DriverRouteController::class, 'destroy']);
        });
    });

    // ── Passenger authenticated endpoints ─────────────────────────────────
    Route::prefix('bookings')->middleware('auth:api')->group(function () {
        Route::post('/',              [PassengerBookingController::class, 'store']);
        Route::get('/',               [PassengerBookingController::class, 'index']);
        Route::get('{id}',            [PassengerBookingController::class, 'show']);
        Route::post('{id}/pay',       [PassengerBookingController::class, 'pay']);
        Route::post('{id}/cancel',    [PassengerBookingController::class, 'cancel']);
        Route::post('{id}/review',    [PassengerBookingController::class, 'review']);
    });
});
