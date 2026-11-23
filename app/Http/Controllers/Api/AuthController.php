<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user account (POST /api/register).
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? User::ROLE_CUSTOMER,
        ]);

        $tokenName = $request->input('device_name', 'CargoTrace-API-Token');
        $token = $user->createToken($tokenName)->plainTextToken;

        AuditLog::log($user->id, 'api_register', 'User', $user->id, null, [
            'email' => $user->email,
            'role' => $user->role,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'User registered successfully.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 201);
    }

    /**
     * Authenticate user and issue Sanctum token (POST /api/login).
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid email credentials or password.'],
            ]);
        }

        $tokenName = $validated['device_name'] ?? 'CargoTrace-API-Token';
        $token = $user->createToken($tokenName)->plainTextToken;

        AuditLog::log($user->id, 'api_login', 'User', $user->id, null, ['ip' => $request->ip()]);

        return response()->json([
            'message' => 'Authenticated successfully.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Revoke active token (POST /api/logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Token revoked successfully.',
        ]);
    }

    /**
     * Get authenticated user profile (GET /api/user).
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }
}
