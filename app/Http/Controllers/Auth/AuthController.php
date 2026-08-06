<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'user_name' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('user_name', $request->user_name)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'user_name' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($request->filled('device_token')) {
            $user->addDeviceToken($request->device_token);
        }


        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'user_name' => $user->user_name,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'device_count' => count($user->device_token ?? []),
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }
        $user->currentAccessToken()->delete();
        if ($request->has('device_token')) {
            $user->removeDeviceToken($request->device_token);
        }

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function logoutAllDevices(Request $request)
{
    $user = $request->user();
    
    if (!$user) {
        return response()->json([
            'message' => 'Unauthenticated'
        ], 401);
    }

    $user->tokens()->delete();
    $user->update([
        'device_token' => null
    ]);

    return response()->json([
        'message' => 'Logged out from all devices successfully'
    ]);
}
}
