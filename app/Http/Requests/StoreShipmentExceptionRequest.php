<?php

namespace App\Http\Requests;

use App\Services\ExceptionService;
use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shipment = $this->route('shipment');
        return $this->user()?->can('manageExceptions', $shipment) ?? false;
    }

    public function rules(): array
    {
        $supportedTypes = ExceptionService::SUPPORTED_TYPES;
        $lowerTypes = array_map('strtolower', $supportedTypes);
        $allAllowedTypes = implode(',', array_merge($supportedTypes, $lowerTypes));

        return [
            'type' => ['required', 'string', 'in:' . $allAllowedTypes],
            'severity' => ['required', 'string', 'in:LOW,MEDIUM,HIGH,CRITICAL,low,medium,high,critical'],
            'description' => ['required', 'string', 'max:1000'],
            'detected_at' => ['nullable', 'date'],
            'estimated_delay_hours' => ['nullable', 'numeric', 'min:0'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
