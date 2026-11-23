<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shipment = $this->route('shipment');
        return $this->user()?->can('update', $shipment) ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['sometimes', 'required', 'exists:users,id'],
            'carrier_id' => ['sometimes', 'required', 'exists:carriers,id'],
            'origin_location_id' => ['sometimes', 'required', 'exists:locations,id'],
            'destination_location_id' => ['sometimes', 'required', 'exists:locations,id'],
            'cargo_type' => ['sometimes', 'required', 'string', 'max:255'],
            'weight' => ['sometimes', 'required', 'numeric', 'min:0'],
            'volume' => ['sometimes', 'required', 'numeric', 'min:0'],
            'expected_departure' => ['sometimes', 'required', 'date'],
            'expected_arrival' => ['sometimes', 'required', 'date'],
            'estimated_arrival' => ['nullable', 'date'],
            'current_status' => ['sometimes', 'required', 'string'],
            'allow_terminal_reversal' => ['nullable', 'boolean'],
            'reversal_reason' => ['nullable', 'string', 'max:500'],
            'items' => ['nullable', 'array'],
            'items.*.item_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.weight' => ['nullable', 'numeric', 'min:0'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
