<?php

use App\Http\Controllers\Web\AuditLogController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\CarrierController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\LocaleController;
use App\Http\Controllers\Web\LocationController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\ShipmentController;
use App\Http\Controllers\Web\ShipmentExceptionController;
use App\Http\Controllers\Web\TrackingEventController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

// Public Locale Switcher Route
Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Shipments (All roles can view their scoped list & details)
    Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');

    // Operations (Logistics Operator & Administrator)
    Route::middleware('role:admin,operator')->group(function () {
        Route::get('/shipment/create', [ShipmentController::class, 'create'])->name('shipments.create');
        Route::post('/shipment', [ShipmentController::class, 'store'])->name('shipments.store');
        Route::get('/shipments/{shipment}/edit', [ShipmentController::class, 'edit'])->name('shipments.edit');
        Route::put('/shipments/{shipment}', [ShipmentController::class, 'update'])->name('shipments.update');
        Route::delete('/shipments/{shipment}', [ShipmentController::class, 'destroy'])->name('shipments.destroy');

        Route::post('/shipments/{shipment}/tracking-events', [TrackingEventController::class, 'store'])->name('tracking-events.store');
        Route::post('/shipments/{shipment}/exceptions', [ShipmentExceptionController::class, 'store'])->name('exceptions.store');
        Route::post('/exceptions/{exception}/investigate', [ShipmentExceptionController::class, 'investigate'])->name('exceptions.investigate');
        Route::post('/exceptions/{exception}/resolve', [ShipmentExceptionController::class, 'resolve'])->name('exceptions.resolve');
    });

    // Notifications (All authenticated users)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');

    // Admin Only Management (Carriers, Locations, Users, Audit Logs)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('carriers', CarrierController::class)->except(['create', 'show', 'edit']);
        Route::resource('locations', LocationController::class)->except(['create', 'show', 'edit']);
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
});
