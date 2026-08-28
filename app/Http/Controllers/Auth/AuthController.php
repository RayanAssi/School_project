<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'user_name' => 'required|string',
            'password' => 'required|string',
            'fcm_token' => 'nullable|string',
        ]);

        $user = User::where('user_name', $request->user_name)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'user_name' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($request->filled('fcm_token')) {
            $user->setFcmToken($request->fcm_token);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
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
        $user->removeFcmToken();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function saveFCMToken(Request $request)
{
    Log::info('Request Headers:', $request->headers->all());
    Log::info('Authorization Header:', [$request->header('Authorization')]);
    try {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = Auth::user();
Log::info('User:', [$user]);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم غير مسجل الدخول',
            ], 401);
        }

        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ FCM Token بنجاح',
            'fcm_token' => $user->fcm_token
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ: ' . $e->getMessage(),
        ], 500);
    }
}
}
