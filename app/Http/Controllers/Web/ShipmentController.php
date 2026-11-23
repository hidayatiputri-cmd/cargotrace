<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ShipmentService;
use App\Services\TrackingService;
use App\Services\WeatherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    public function __construct(
        protected ShipmentService $shipmentService,
        protected TrackingService $trackingService,
        protected WeatherService $weatherService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $filters = $request->only(['status', 'is_delayed', 'carrier_id', 'customer_id', 'search']);
        $shipments = $this->shipmentService->listShipments($user, $filters, 10);
        $carriers = Carrier::where('status', 'active')->get();
        $customers = ($user->isAdmin() || $user->isOperator())
            ? User::where('role', User::ROLE_CUSTOMER)->get()
            : collect();

        return view('shipments.index', compact('shipments', 'carriers', 'customers', 'filters'));
    }

    public function show(Shipment $shipment, Request $request): View
    {
        // Policy check
        Gate::authorize('view', $shipment);

        $shipment = $this->shipmentService->getShipmentWithFullDetails($shipment);
        $timeline = $this->trackingService->getTimeline($shipment);
        $locations = Location::all();
        $weather = $this->weatherService->getWeatherForShipment($shipment);

        return view('shipments.show', compact('shipment', 'timeline', 'locations', 'weather'));
    }

    public function create(): View
    {
        Gate::authorize('create', Shipment::class);

        $carriers = Carrier::where('status', 'active')->get();
        $locations = Location::all();
        $customers = User::where('role', User::ROLE_CUSTOMER)->get();

        return view('shipments.create', compact('carriers', 'locations', 'customers'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Shipment::class);

        $validated = $request->validate([
            'shipment_number' => ['nullable', 'string', 'max:50', 'unique:shipments,shipment_number'],
            'customer_id' => ['required', 'exists:users,id'],
            'carrier_id' => ['required', 'exists:carriers,id'],
            'origin_location_id' => ['required', 'exists:locations,id', 'different:destination_location_id'],
            'destination_location_id' => ['required', 'exists:locations,id'],
            'cargo_type' => ['required', 'string', 'max:255'],
            'weight' => ['required', 'numeric', 'min:0'],
            'volume' => ['required', 'numeric', 'min:0'],
            'expected_departure' => ['required', 'date'],
            'expected_arrival' => ['required', 'date', 'after:expected_departure'],
            'estimated_arrival' => ['nullable', 'date'],
            'current_status' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.item_name' => ['required_with:items', 'string'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.weight' => ['nullable', 'numeric', 'min:0'],
            'items.*.description' => ['nullable', 'string'],
        ]);

        $shipment = $this->shipmentService->createShipment($validated, $request->user());

        return redirect()->route('shipments.show', $shipment)
            ->with('success', "Shipment #{$shipment->shipment_number} created successfully.");
    }

    public function edit(Shipment $shipment): View
    {
        Gate::authorize('update', $shipment);

        $carriers = Carrier::where('status', 'active')->get();
        $locations = Location::all();
        $customers = User::where('role', User::ROLE_CUSTOMER)->get();
        $shipment->load('items');

        return view('shipments.edit', compact('shipment', 'carriers', 'locations', 'customers'));
    }

    public function update(Request $request, Shipment $shipment): RedirectResponse
    {
        Gate::authorize('update', $shipment);

        $validated = $request->validate([
            'customer_id' => ['required', 'exists:users,id'],
            'carrier_id' => ['required', 'exists:carriers,id'],
            'origin_location_id' => ['required', 'exists:locations,id', 'different:destination_location_id'],
            'destination_location_id' => ['required', 'exists:locations,id'],
            'cargo_type' => ['required', 'string', 'max:255'],
            'weight' => ['required', 'numeric', 'min:0'],
            'volume' => ['required', 'numeric', 'min:0'],
            'expected_departure' => ['required', 'date'],
            'expected_arrival' => ['required', 'date'],
            'estimated_arrival' => ['nullable', 'date'],
            'current_status' => ['required', 'string'],
            'allow_terminal_reversal' => ['nullable', 'boolean'],
            'reversal_reason' => ['nullable', 'string', 'max:500'],
            'items' => ['nullable', 'array'],
            'items.*.item_name' => ['required_with:items', 'string'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.weight' => ['nullable', 'numeric', 'min:0'],
            'items.*.description' => ['nullable', 'string'],
        ]);

        $this->shipmentService->updateShipment($shipment, $validated, $request->user());

        return redirect()->route('shipments.show', $shipment)
            ->with('success', "Shipment #{$shipment->shipment_number} updated successfully.");
    }

    public function destroy(Shipment $shipment): RedirectResponse
    {
        Gate::authorize('delete', $shipment);

        $num = $shipment->shipment_number;
        $shipment->delete();

        return redirect()->route('shipments.index')
            ->with('success', "Shipment #{$num} was deleted.");
    }
}
