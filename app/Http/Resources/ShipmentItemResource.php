<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shipment_id' => $this->shipment_id,
            'item_name' => $this->item_name,
            'quantity' => $this->quantity,
            'weight' => $this->weight !== null ? (float) $this->weight : 0.0,
            'description' => $this->description,
        ];
    }
}
