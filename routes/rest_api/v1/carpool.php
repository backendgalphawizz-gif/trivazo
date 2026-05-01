<?php

use App\Http\Controllers\RestAPI\v1\CarPool\DriverAuthController;
use App\Http\Controllers\RestAPI\v1\CarPool\DriverRouteController;
use App\Http\Controllers\RestAPI\v1\CarPool\DriverWalletController;
use App\Http\Controllers\RestAPI\v1\CarPool\VehicleCategoryController;
use App\Http\Controllers\RestAPI\v1\CarPool\PassengerBookingController;
use App\Http\Controllers\RestAPI\v1\CarPool\PassengerProfileController;
use App\Http\Controllers\RestAPI\v1\CarPool\PassengerRouteController;
use App\Http\Controllers\RestAPI\v1\CarPool\PassengerSavedPassengerController;
use App\Http\Controllers\RestAPI\v1\CarPool\PassengerTrackingController;
use App\Http\Controllers\RestAPI\v1\CouponController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CarPool REST API Routes  — /api/v1/carpool/*
|--------------------------------------------------------------------------
|
| Public:
|   GET  /api/v1/carpool/routes/search      — search available trips
|   GET  /api/v1/carpool/routes/{id}        — trip detail
|   GET  /api/v1/carpool/driver/{id}/profile    — public driver profile
|   GET  /api/v1/carpool/vehicle-categories     — list vehicle types (?active_only=0 for all)
|
| Passenger (auth:api — normal app user):
|   GET  /api/v1/carpool/passenger/profile       — my profile + stats
|   GET  /api/v1/carpool/passenger/active        — active ride
|   GET  /api/v1/carpool/passenger/bookings      — booking history (filter: status)
|   GET  /api/v1/carpool/passenger/bookings/{id} — booking detail
|   POST /api/v1/carpool/passenger/bookings      — create booking
|   POST /api/v1/carpool/passenger/bookings/{id}/pay     — confirm payment
|   POST /api/v1/carpool/passenger/bookings/{id}/cancel  — cancel booking
|   POST /api/v1/carpool/passenger/bookings/{id}/review  — submit review
|   GET  /api/v1/carpool/passenger/reviews       — my reviews
|   GET  /api/v1/carpool/passenger/coupons       — store coupons (same as /api/v1/coupon/list)
|   GET  /api/v1/carpool/passenger/coupons/applicable — cart-applicable coupons
|   GET  /api/v1/carpool/passenger/track/{id}            — full tracking info
|   GET  /api/v1/carpool/passenger/track/{id}/location   — driver location (polling)
|
| Driver (auth:api — same User Passport token as customer; driver resolved by users.phone = carpool_drivers.phone):
|   POST .../driver/register — Bearer required. JSON: { status, message, driver } — driver includes nested user { id,name,f_name,l_name,email,phone,country_code,image_full_url }; no top-level token.
|   POST .../driver/login    — JSON: { status, token, driver } — token is User Passport (LaravelAuthApp); driver includes nested user (same shape).
|   GET  .../driver/profile  — JSON: { status, driver } with nested user.
|   PUT / POST .../driver/profile — same handler. PUT: raw JSON only (multipart on PUT returns 422 — use POST). Field `vehicle_type` works like `vehicle_category_name`. Files: POST + multipart.
|   POST logout, wallet/*, my-routes/*, POST location — same Bearer.
|
*/

Route::prefix('carpool')->group(function () {

    // ──────────────────────────────────────────────────────────────────────
    // PUBLIC ENDPOINTS
    // ──────────────────────────────────────────────────────────────────────

    // Trip / Route search
    Route::prefix('routes')->group(function () {
        Route::get('search', [PassengerRouteController::class, 'search']);
        Route::get('{id}',   [PassengerRouteController::class, 'show']);
    });

    // Public driver profile (passenger can view before booking)
    Route::get('driver/{driverId}/profile', [PassengerProfileController::class, 'driverProfile']);

    Route::get('vehicle-categories', [VehicleCategoryController::class, 'index']);

    // Driver login (password = same as store User for that phone)
    Route::prefix('driver')->group(function () {
        Route::post('login', [DriverAuthController::class, 'login']);
    });

    // Driver register — requires customer Bearer token (`auth:api`); identity + password synced from User
    Route::prefix('driver')->middleware('auth:api')->group(function () {
        Route::post('register', [DriverAuthController::class, 'register']);
    });

    // ──────────────────────────────────────────────────────────────────────
    // PASSENGER (USER) AUTHENTICATED ENDPOINTS  — guard: auth:api
    // ──────────────────────────────────────────────────────────────────────
    Route::prefix('passenger')->middleware('auth:api')->group(function () {

        // Profile & stats
        Route::get('profile', [PassengerProfileController::class, 'profile']);

        // Active ride
        Route::get('active', [PassengerProfileController::class, 'activeRide']);

        // Booking CRUD + actions
        Route::prefix('bookings')->group(function () {
            Route::get('/',              [PassengerBookingController::class, 'index']);
            Route::post('/',             [PassengerBookingController::class, 'store']);
            Route::get('{id}',           [PassengerBookingController::class, 'show']);
            Route::post('{id}/pay',      [PassengerBookingController::class, 'pay']);
            Route::post('{id}/cancel',   [PassengerBookingController::class, 'cancel']);
            Route::post('{id}/review',   [PassengerBookingController::class, 'review']);
            Route::post('{id}/passengers', [PassengerBookingController::class, 'addPassenger']);
        });

        // Saved passengers master list
     
            Route::get('get-passengers',            [PassengerSavedPassengerController::class, 'index']);
            Route::post('add-passengers',           [PassengerSavedPassengerController::class, 'store']);
            Route::post('update-passenger/{id}',     [PassengerSavedPassengerController::class, 'update']);
            Route::delete('delete-passenger/{id}',  [PassengerSavedPassengerController::class, 'destroy']);
    

        // My reviews
        Route::get('reviews', [PassengerProfileController::class, 'myReviews']);

        // Store coupons (reuses main CouponController; query: limit, offset)
        Route::get('coupons', [CouponController::class, 'list']);
        Route::get('coupons/applicable', [CouponController::class, 'applicable_list']);

        // Live tracking
        Route::prefix('track')->group(function () {
            Route::get('{id}',          [PassengerTrackingController::class, 'track']);
            Route::get('{id}/location', [PassengerTrackingController::class, 'driverLocation']);
        });
    });

    // ──────────────────────────────────────────────────────────────────────
    // DRIVER AUTHENTICATED ENDPOINTS  — guard: auth:api (User); CarPoolDriver resolved by users.phone
    // ──────────────────────────────────────────────────────────────────────
    Route::prefix('driver')->middleware('auth:api')->group(function () {
        Route::post('logout',         [DriverAuthController::class, 'logout']);
        Route::get('profile',         [DriverAuthController::class, 'profile']);
        Route::put('profile',         [DriverAuthController::class, 'updateProfile']);
        // POST alias: PHP often does not populate uploaded files on PUT multipart — use POST + multipart for images/docs.
        Route::post('profile',        [DriverAuthController::class, 'updateProfile']);

        // Live location push (driver app sends GPS every ~10s)
        Route::post('location',       [DriverRouteController::class, 'updateLocation']);

        // Wallet
        Route::prefix('wallet')->group(function () {
            Route::get('/',             [DriverWalletController::class, 'wallet']);
            Route::get('transactions',  [DriverWalletController::class, 'transactions']);
            Route::post('withdraw',     [DriverWalletController::class, 'requestWithdrawal']);
            Route::get('withdrawals',   [DriverWalletController::class, 'withdrawals']);
        });

        // Ride Route management
        Route::prefix('my-routes')->group(function () {
            Route::post('/',             [DriverRouteController::class, 'store']);
            Route::get('/',              [DriverRouteController::class, 'myRoutes']);
            Route::get('{id}',           [DriverRouteController::class, 'show']);
            Route::post('{id}/depart',   [DriverRouteController::class, 'depart']);
            Route::post('{id}/complete', [DriverRouteController::class, 'complete']);
            Route::delete('{id}',        [DriverRouteController::class, 'destroy']);
        });
    });
});

