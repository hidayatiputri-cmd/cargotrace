<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrackingEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shipment = $this->route('shipment');
        return $this->user()?->can('addTrackingEvent', $shipment) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'max:50'],
            'event_type' => ['required', 'string', 'max:50'],
            'event_time' => ['nullable', 'date'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'description' => ['required', 'string', 'max:1000'],
            'estimated_arrival' => ['nullable', 'date'],
            'allow_terminal_reversal' => ['nullable', 'boolean'],
            'reversal_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
