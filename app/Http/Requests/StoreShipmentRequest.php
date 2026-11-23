<?php

namespace App\Http\Requests;

use App\Models\Shipment;
use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Shipment::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'shipment_number' => ['nullable', 'string', 'max:50', 'unique:shipments,shipment_number'],
            'customer_id' => ['required', 'exists:users,id'],
            'carrier_id' => ['required', 'exists:carriers,id'],
            'origin_location_id' => ['required', 'exists:locations,id'],
            'destination_location_id' => ['required', 'exists:locations,id', 'different:origin_location_id'],
            'cargo_type' => ['required', 'string', 'max:255'],
            'weight' => ['required', 'numeric', 'min:0'],
            'volume' => ['required', 'numeric', 'min:0'],
            'expected_departure' => ['required', 'date'],
            'expected_arrival' => ['required', 'date', 'after:expected_departure'],
            'estimated_arrival' => ['nullable', 'date'],
            'current_status' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.item_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.weight' => ['nullable', 'numeric', 'min:0'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
