<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Create token response with expiration.
     *
     * @param User $user
     * @return array
     */
    private function createTokenResponse($user)
    {
        $tokenResult = $user->createToken('API TOKEN');
        $expiresAt = now()->addDays(config('sanctum.expiration', 1)); // Default 1 day

        return [
            'token' => $tokenResult->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * Register a new user.
     *
     * @param RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_admin' => false, // Default value
            ]);

            $tokenResponse = $this->createTokenResponse($user);

            return response()->json([
                'status' => true,
                'message' => 'User created successfully',
                'token' => $tokenResponse['token'],
                'expires_at' => $tokenResponse['expires_at'],
                'user' => new UserResource($user),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login an existing user.
     *
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email & password do not match our records',
                ], 401);
            }

            $tokenResponse = $this->createTokenResponse($user);

            return response()->json([
                'status' => true,
                'message' => 'User logged in successfully',
                'token' => $tokenResponse['token'],
                'expires_at' => $tokenResponse['expires_at'],
                'user' => new UserResource($user),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Login failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the authenticated user's profile.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function user()
    {
        return response()->json([
            'status' => true,
            'message' => 'Profile retrieved successfully',
            'data' => new UserResource(auth()->user()),
        ], 200);
    }

    /**
     * Logout the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try {
            auth()->user()->tokens()->delete();

            return response()->json([
                'status' => true,
                'message' => 'User logged out successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Logout failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
