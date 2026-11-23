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

// Public Authentication
Route::post('/login', [AuthController::class, 'login']);

// Protected API Routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/me', [AuthController::class, 'me']);

    // Shipments (Scoped to user role)
    Route::get('/shipments', [ShipmentController::class, 'index']);
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);

    // Operators & Admins
    Route::middleware('role:admin,operator')->group(function () {
        Route::post('/shipments', [ShipmentController::class, 'store']);
        Route::put('/shipments/{shipment}', [ShipmentController::class, 'update']);
        Route::post('/shipments/{shipment}/tracking-events', [TrackingEventController::class, 'store']);
        Route::post('/shipments/{shipment}/exceptions', [ShipmentExceptionController::class, 'store']);
        Route::post('/exceptions/{exception}/resolve', [ShipmentExceptionController::class, 'resolve']);
    });

    // Admins Only
    Route::middleware('role:admin')->group(function () {
        Route::get('/carriers', [CarrierController::class, 'index']);
        Route::post('/carriers', [CarrierController::class, 'store']);
        Route::get('/locations', [LocationController::class, 'index']);
        Route::post('/locations', [LocationController::class, 'store']);
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
    });
});
