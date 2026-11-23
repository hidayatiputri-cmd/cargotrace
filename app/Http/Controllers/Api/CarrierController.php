<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCarrierRequest;
use App\Http\Resources\CarrierResource;
use App\Models\Carrier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CarrierController extends Controller
{
    /**
     * List carriers (GET /api/carriers).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Carrier::query();

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        return CarrierResource::collection($query->orderBy('name')->get());
    }

    /**
     * Create a new carrier (POST /api/carriers).
     */
    public function store(StoreCarrierRequest $request): JsonResponse
    {
        $carrier = Carrier::create($request->validated());

        return response()->json([
            'message' => 'Carrier created successfully.',
            'data' => new CarrierResource($carrier),
        ], 201);
    }
}
