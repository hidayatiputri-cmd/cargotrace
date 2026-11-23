<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Services\ExceptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ShipmentExceptionController extends Controller
{
    public function __construct(
        protected ExceptionService $exceptionService
    ) {}

    public function store(Request $request, Shipment $shipment): RedirectResponse
    {
        Gate::authorize('manageExceptions', $shipment);

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:60'],
            'severity' => ['required', 'in:low,medium,high,critical'],
            'description' => ['required', 'string'],
            'detected_at' => ['required', 'date'],
        ]);

        $this->exceptionService->logException($shipment, $validated, $request->user());

        return redirect()->route('shipments.show', $shipment)
            ->with('warning', 'Shipment exception reported and flagged.');
    }

    public function resolve(Request $request, ShipmentException $exception): RedirectResponse
    {
        Gate::authorize('manageExceptions', $exception->shipment);

        $validated = $request->validate([
            'resolution_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->exceptionService->resolveException($exception, $validated, $request->user());

        return redirect()->route('shipments.show', $exception->shipment)
            ->with('success', 'Shipment exception resolved.');
    }
}
