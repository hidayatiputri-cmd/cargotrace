<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShipmentRequest;
use App\Http\Requests\UpdateShipmentRequest;
use App\Http\Resources\ShipmentResource;
use App\Models\Shipment;
use App\Services\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ShipmentController extends Controller
{
    public function __construct(
        protected ShipmentService $shipmentService
    ) {}

    /**
     * List shipments with server-side pagination, search, and filters (GET /api/shipments).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['status', 'is_delayed', 'carrier_id', 'customer_id', 'search']);
        $perPage = $request->integer('per_page', 15);
        $shipments = $this->shipmentService->listShipments($request->user(), $filters, $perPage);

        return ShipmentResource::collection($shipments);
    }

    /**
     * Retrieve single shipment with eager loaded relationships (GET /api/shipments/{id}).
     */
    public function show(Shipment $shipment): JsonResponse
    {
        Gate::authorize('view', $shipment);

        $details = $this->shipmentService->getShipmentWithFullDetails($shipment);

        return response()->json([
            'data' => new ShipmentResource($details),
        ]);
    }

    /**
     * Create a new shipment (POST /api/shipments).
     */
    public function store(StoreShipmentRequest $request): JsonResponse
    {
        $shipment = $this->shipmentService->createShipment($request->validated(), $request->user());
        $details = $this->shipmentService->getShipmentWithFullDetails($shipment);

        return response()->json([
            'message' => 'Shipment created successfully.',
            'data' => new ShipmentResource($details),
        ], 201);
    }

    /**
     * Update an existing shipment (PUT/PATCH /api/shipments/{id}).
     */
    public function update(UpdateShipmentRequest $request, Shipment $shipment): JsonResponse
    {
        $updated = $this->shipmentService->updateShipment($shipment, $request->validated(), $request->user());
        $details = $this->shipmentService->getShipmentWithFullDetails($updated);

        return response()->json([
            'message' => 'Shipment updated successfully.',
            'data' => new ShipmentResource($details),
        ]);
    }

    /**
     * Delete a shipment (DELETE /api/shipments/{id}).
     */
    public function destroy(Shipment $shipment): JsonResponse
    {
        Gate::authorize('delete', $shipment);

        $this->shipmentService->deleteShipment($shipment, request()->user());

        return response()->json([
            'message' => "Shipment #{$shipment->shipment_number} deleted successfully.",
        ]);
    }
}
