<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LocationController extends Controller
{
    /**
     * List locations (GET /api/locations).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Location::query();

        if ($request->has('country')) {
            $query->where('country', $request->query('country'));
        }

        return LocationResource::collection($query->orderBy('name')->get());
    }

    /**
     * Create a new location (POST /api/locations).
     */
    public function store(StoreLocationRequest $request): JsonResponse
    {
        $location = Location::create($request->validated());

        return response()->json([
            'message' => 'Location node created successfully.',
            'data' => new LocationResource($location),
        ], 201);
    }
}
