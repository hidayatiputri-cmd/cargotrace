<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Services\ShipmentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ShipmentService $shipmentService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $metrics = $this->shipmentService->getDashboardMetrics($user);

        // Recent shipments scoped by user
        $recentShipmentsQuery = Shipment::query()
            ->with(['carrier', 'originLocation', 'destinationLocation', 'customer'])
            ->latest();

        if ($user->isCustomer()) {
            $recentShipmentsQuery->where('customer_id', $user->id);
        }

        $recentShipments = $recentShipmentsQuery->take(6)->get();

        // Recent exceptions
        $recentExceptionsQuery = ShipmentException::query()
            ->with(['shipment.carrier', 'reporter'])
            ->latest('detected_at');

        if ($user->isCustomer()) {
            $recentExceptionsQuery->whereHas('shipment', function ($q) use ($user) {
                $q->where('customer_id', $user->id);
            });
        }

        $recentExceptions = $recentExceptionsQuery->take(5)->get();

        // Additional stats for Admin
        $adminStats = [];
        if ($user->isAdmin()) {
            $adminStats = [
                'total_carriers' => Carrier::count(),
                'total_locations' => Location::count(),
                'recent_audit_logs' => AuditLog::with('user')->latest()->take(6)->get(),
            ];
        }

        return view('dashboard', compact('user', 'metrics', 'recentShipments', 'recentExceptions', 'adminStats'));
    }
}
