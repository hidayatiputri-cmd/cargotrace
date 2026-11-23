<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CarrierController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Carrier::all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Carrier::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'unique:carriers,code'],
            'contact_info' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $carrier = Carrier::create($validated);

        return response()->json([
            'message' => 'Carrier created.',
            'carrier' => $carrier,
        ], 201);
    }
}
