<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShipmentExceptionRequest;
use App\Http\Requests\UpdateShipmentExceptionRequest;
use App\Http\Resources\ShipmentExceptionResource;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Services\ExceptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ShipmentExceptionController extends Controller
{
    public function __construct(
        protected ExceptionService $exceptionService
    ) {}

    /**
     * List exceptions for a shipment (GET /api/shipments/{id}/exceptions).
     */
    public function index(Shipment $shipment): AnonymousResourceCollection
    {
        Gate::authorize('view', $shipment);

        $exceptions = $shipment->exceptions()
            ->with('reporter')
            ->orderBy('detected_at', 'desc')
            ->get();

        return ShipmentExceptionResource::collection($exceptions);
    }

    /**
     * Log a new exception for a shipment (POST /api/shipments/{id}/exceptions).
     */
    public function store(StoreShipmentExceptionRequest $request, Shipment $shipment): JsonResponse
    {
        $exception = $this->exceptionService->logException($shipment, $request->validated(), $request->user());

        return response()->json([
            'message' => 'Shipment exception reported successfully.',
            'data' => new ShipmentExceptionResource($exception->load('reporter')),
        ], 201);
    }

    /**
     * Update an exception status or details (PATCH /api/exceptions/{id}).
     */
    public function update(UpdateShipmentExceptionRequest $request, ShipmentException $exception): JsonResponse
    {
        $updated = $this->exceptionService->updateException($exception, $request->validated(), $request->user());

        return response()->json([
            'message' => 'Shipment exception updated successfully.',
            'data' => new ShipmentExceptionResource($updated->load('reporter')),
        ]);
    }

    /**
     * Advance exception to investigating (POST /api/exceptions/{id}/investigate).
     */
    public function investigate(Request $request, ShipmentException $exception): JsonResponse
    {
        Gate::authorize('manageExceptions', $exception->shipment);

        $validated = $request->validate([
            'investigation_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $investigating = $this->exceptionService->startInvestigation($exception, $validated, $request->user());

        return response()->json([
            'message' => 'Incident investigation commenced.',
            'data' => new ShipmentExceptionResource($investigating->load('reporter')),
        ]);
    }

    /**
     * Mark exception as resolved (POST /api/exceptions/{id}/resolve).
     */
    public function resolve(Request $request, ShipmentException $exception): JsonResponse
    {
        Gate::authorize('manageExceptions', $exception->shipment);

        $validated = $request->validate([
            'resolution_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $resolved = $this->exceptionService->resolveException($exception, $validated, $request->user());

        return response()->json([
            'message' => 'Shipment exception resolved.',
            'data' => new ShipmentExceptionResource($resolved->load('reporter')),
        ]);
    }
}
