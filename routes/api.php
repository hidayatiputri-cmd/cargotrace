<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarrierController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\ShipmentExceptionController;
use App\Http\Controllers\Api\TrackingEventController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Authentication API Endpoints
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register'])->name('api.register');
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

/*
|--------------------------------------------------------------------------
| Protected Sanctum API Endpoints
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Current User Profile & Session Management
    Route::get('/user', [AuthController::class, 'me'])->name('api.user');
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // Shipments Core Endpoints
    Route::get('/shipments', [ShipmentController::class, 'index'])->name('api.shipments.index');
    Route::post('/shipments', [ShipmentController::class, 'store'])->name('api.shipments.store');
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show'])->name('api.shipments.show');
    Route::match(['put', 'patch'], '/shipments/{shipment}', [ShipmentController::class, 'update'])->name('api.shipments.update');
    Route::delete('/shipments/{shipment}', [ShipmentController::class, 'destroy'])->name('api.shipments.destroy');

    // Historical Tracking Endpoints
    Route::get('/shipments/{shipment}/tracking', [TrackingEventController::class, 'index'])->name('api.shipments.tracking.index');
    Route::post('/shipments/{shipment}/tracking', [TrackingEventController::class, 'store'])->name('api.shipments.tracking.store');
    Route::post('/shipments/{shipment}/tracking-events', [TrackingEventController::class, 'store'])->name('api.shipments.tracking-events.store');

    // Operational Exception Management Endpoints
    Route::get('/shipments/{shipment}/exceptions', [ShipmentExceptionController::class, 'index'])->name('api.shipments.exceptions.index');
    Route::post('/shipments/{shipment}/exceptions', [ShipmentExceptionController::class, 'store'])->name('api.shipments.exceptions.store');
    Route::patch('/exceptions/{exception}', [ShipmentExceptionController::class, 'update'])->name('api.exceptions.update');
    Route::post('/exceptions/{exception}/investigate', [ShipmentExceptionController::class, 'investigate'])->name('api.exceptions.investigate');
    Route::post('/exceptions/{exception}/resolve', [ShipmentExceptionController::class, 'resolve'])->name('api.exceptions.resolve');

    // Master Resources: Carriers
    Route::get('/carriers', [CarrierController::class, 'index'])->name('api.carriers.index');
    Route::post('/carriers', [CarrierController::class, 'store'])->name('api.carriers.store');

    // Master Resources: Locations
    Route::get('/locations', [LocationController::class, 'index'])->name('api.locations.index');
    Route::post('/locations', [LocationController::class, 'store'])->name('api.locations.store');

    // In-App Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('api.notifications.index');
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('api.notifications.read');

    // Audit Trail (Admins only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('api.audit-logs.index');
    });
});
