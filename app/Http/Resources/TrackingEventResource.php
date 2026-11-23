<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackingEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shipment_id' => $this->shipment_id,
            'status' => $this->status,
            'event_type' => $this->event_type,
            'event_time' => $this->event_time?->toIso8601String(),
            'location_id' => $this->location_id,
            'location' => new LocationResource($this->whenLoaded('location')),
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'description' => $this->description,
            'created_by' => $this->created_by,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'is_exception' => $this->isExceptionEvent(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
