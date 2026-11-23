<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Services\ExceptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ShipmentExceptionController extends Controller
{
    public function __construct(
        protected ExceptionService $exceptionService
    ) {}

    public function store(Request $request, Shipment $shipment): JsonResponse
    {
        Gate::authorize('manageExceptions', $shipment);

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:60'],
            'severity' => ['required', 'in:low,medium,high,critical'],
            'description' => ['required', 'string'],
            'detected_at' => ['nullable', 'date'],
        ]);

        $exception = $this->exceptionService->logException($shipment, $validated, $request->user());

        return response()->json([
            'message' => 'Shipment exception reported.',
            'exception' => $exception,
        ], 201);
    }

    public function resolve(Request $request, ShipmentException $exception): JsonResponse
    {
        Gate::authorize('manageExceptions', $exception->shipment);

        $validated = $request->validate([
            'resolution_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $resolved = $this->exceptionService->resolveException($exception, $validated, $request->user());

        return response()->json([
            'message' => 'Shipment exception resolved.',
            'exception' => $resolved,
        ]);
    }
}
