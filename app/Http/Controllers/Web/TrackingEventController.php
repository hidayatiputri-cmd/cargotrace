<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\TrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TrackingEventController extends Controller
{
    public function __construct(
        protected TrackingService $trackingService
    ) {}

    public function store(Request $request, Shipment $shipment): RedirectResponse
    {
        Gate::authorize('addTrackingEvent', $shipment);

        $validated = $request->validate([
            'status' => ['required', 'string'],
            'event_type' => ['required', 'string'],
            'event_time' => ['required', 'date'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'description' => ['required', 'string', 'max:1000'],
            'estimated_arrival' => ['nullable', 'date'],
        ]);

        $this->trackingService->addTrackingEvent($shipment, $validated, $request->user());

        return redirect()->route('shipments.show', $shipment)
            ->with('success', 'New tracking checkpoint logged successfully.');
    }
}
