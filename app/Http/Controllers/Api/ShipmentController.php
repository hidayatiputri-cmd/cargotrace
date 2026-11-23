<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ShipmentController extends Controller
{
    public function __construct(
        protected ShipmentService $shipmentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'is_delayed', 'carrier_id', 'customer_id', 'search']);
        $shipments = $this->shipmentService->listShipments($request->user(), $filters, $request->integer('per_page', 15));

        return response()->json($shipments);
    }

    public function show(Shipment $shipment): JsonResponse
    {
        Gate::authorize('view', $shipment);

        $details = $this->shipmentService->getShipmentWithFullDetails($shipment);

        return response()->json([
            'data' => $details,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Shipment::class);

        $validated = $request->validate([
            'shipment_number' => ['nullable', 'string', 'max:50', 'unique:shipments,shipment_number'],
            'customer_id' => ['required', 'exists:users,id'],
            'carrier_id' => ['required', 'exists:carriers,id'],
            'origin_location_id' => ['required', 'exists:locations,id'],
            'destination_location_id' => ['required', 'exists:locations,id', 'different:origin_location_id'],
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

        return response()->json([
            'message' => 'Shipment created successfully.',
            'data' => $this->shipmentService->getShipmentWithFullDetails($shipment),
        ], 201);
    }

    public function update(Request $request, Shipment $shipment): JsonResponse
    {
        Gate::authorize('update', $shipment);

        $validated = $request->validate([
            'customer_id' => ['sometimes', 'required', 'exists:users,id'],
            'carrier_id' => ['sometimes', 'required', 'exists:carriers,id'],
            'origin_location_id' => ['sometimes', 'required', 'exists:locations,id'],
            'destination_location_id' => ['sometimes', 'required', 'exists:locations,id'],
            'cargo_type' => ['sometimes', 'required', 'string', 'max:255'],
            'weight' => ['sometimes', 'required', 'numeric', 'min:0'],
            'volume' => ['sometimes', 'required', 'numeric', 'min:0'],
            'expected_departure' => ['sometimes', 'required', 'date'],
            'expected_arrival' => ['sometimes', 'required', 'date'],
            'estimated_arrival' => ['nullable', 'date'],
            'current_status' => ['sometimes', 'required', 'string'],
            'items' => ['nullable', 'array'],
        ]);

        $updated = $this->shipmentService->updateShipment($shipment, $validated, $request->user());

        return response()->json([
            'message' => 'Shipment updated successfully.',
            'data' => $this->shipmentService->getShipmentWithFullDetails($updated),
        ]);
    }
}
