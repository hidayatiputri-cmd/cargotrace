<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentExceptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shipment_id' => $this->shipment_id,
            'type' => $this->type,
            'severity' => $this->severity,
            'status' => $this->status,
            'description' => $this->description,
            'detected_at' => $this->detected_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'reported_by' => $this->reported_by,
            'reporter' => new UserResource($this->whenLoaded('reporter')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
