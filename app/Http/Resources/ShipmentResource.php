<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shipment_number' => $this->shipment_number,
            'customer_id' => $this->customer_id,
            'customer' => new UserResource($this->whenLoaded('customer')),
            'carrier_id' => $this->carrier_id,
            'carrier' => new CarrierResource($this->whenLoaded('carrier')),
            'origin_location_id' => $this->origin_location_id,
            'origin_location' => new LocationResource($this->whenLoaded('originLocation')),
            'destination_location_id' => $this->destination_location_id,
            'destination_location' => new LocationResource($this->whenLoaded('destinationLocation')),
            'cargo_type' => $this->cargo_type,
            'weight' => (float) $this->weight,
            'volume' => (float) $this->volume,
            'expected_departure' => $this->expected_departure?->toIso8601String(),
            'expected_arrival' => $this->expected_arrival?->toIso8601String(),
            'estimated_arrival' => $this->estimated_arrival?->toIso8601String(),
            'current_status' => $this->current_status,
            'is_delayed' => (bool) $this->is_delayed,
            'delay_hours' => (int) $this->delay_hours,
            'delay_severity' => $this->delay_severity,
            'delay_reason' => $this->delay_reason,
            'current_location' => $this->current_location_description,
            'items_count' => $this->items_count ?? $this->items?->count(),
            'items' => ShipmentItemResource::collection($this->whenLoaded('items')),
            'tracking_events' => TrackingEventResource::collection($this->whenLoaded('trackingEvents')),
            'exceptions' => ShipmentExceptionResource::collection($this->whenLoaded('exceptions')),
            'open_exceptions_count' => $this->open_exceptions_count ?? $this->openExceptions?->count(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
