<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'يجب تسجيل الدخول أولاً'], 401);
        }
        $user = Auth::user();

        if ($user->user_type !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
        }

        $admin = $user->administrator;
        if (!$admin) {
            return response()->json(['message' => 'بيانات المدير غير مكتملة'], 403);
        }
            $adminRole = $admin->role; // 'admin' أو 'admin_assistant'

    if ($adminRole === 'admin_assistant') {
        return response()->json(['message' => 'مساعد المدير غير مسموح له بهذه العملية'], 403);
    }

        return $next($request);
    }
}
