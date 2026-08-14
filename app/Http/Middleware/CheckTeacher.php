<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckTeacher
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'يجب تسجيل الدخول أولاً'], 401);
        }
        
        $user = Auth::user();

        
        if ($user->user_type !== 'teacher' && $user->user_type !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول. يجب أن تكون معلم أو مدير'], 403);
        }

        
        if ($user->user_type === 'teacher') {
            $teacher = $user->teacher;
            if (!$teacher) {
                return response()->json(['message' => 'بيانات المعلم غير مكتملة'], 403);
            }
        }

        return $next($request);
    }
}