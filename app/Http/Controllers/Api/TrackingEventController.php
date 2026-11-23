<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrackingEventRequest;
use App\Http\Resources\TrackingEventResource;
use App\Models\Shipment;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TrackingEventController extends Controller
{
    public function __construct(
        protected TrackingService $trackingService
    ) {}

    /**
     * Get historical tracking timeline for a shipment (GET /api/shipments/{id}/tracking).
     */
    public function index(Shipment $shipment): AnonymousResourceCollection
    {
        Gate::authorize('view', $shipment);

        $timeline = $this->trackingService->getTimeline($shipment);

        return TrackingEventResource::collection($timeline);
    }

    /**
     * Add tracking checkpoint event to a shipment (POST /api/shipments/{id}/tracking).
     */
    public function store(StoreTrackingEventRequest $request, Shipment $shipment): JsonResponse
    {
        $event = $this->trackingService->addTrackingEvent($shipment, $request->validated(), $request->user());

        return response()->json([
            'message' => 'Tracking checkpoint registered successfully.',
            'data' => new TrackingEventResource($event->load(['location', 'creator'])),
        ], 201);
    }
}
