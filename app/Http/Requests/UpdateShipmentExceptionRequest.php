<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exception = $this->route('exception');
        return $this->user()?->can('manageExceptions', $exception->shipment) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', 'string', 'in:OPEN,INVESTIGATING,RESOLVED,open,investigating,resolved'],
            'severity' => ['nullable', 'string', 'in:LOW,MEDIUM,HIGH,CRITICAL,low,medium,high,critical'],
            'description' => ['nullable', 'string', 'max:1000'],
            'investigation_notes' => ['nullable', 'string', 'max:1000'],
            'resolution_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
