<?php

namespace App\Http\Requests;

use App\Models\Carrier;
use Illuminate\Foundation\Http\FormRequest;

class StoreCarrierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Carrier::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'unique:carriers,code'],
            'contact_info' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
