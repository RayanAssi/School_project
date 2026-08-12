<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    //عرض كل المستخدمين
    public function index()
    {
        $users = User::paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب المستخدمين بنجاح',
            'data' => $users
        ]);
    }
    //عرض معلومات المستخدم الحالي 
    public function show()
    {
        return response()->json([
            'user' => Auth::user(),
            'email' => Auth::user()->email,
            'user_type' => Auth::user()->user_type,
            'device_count' => count(Auth::user()->device_token ?? []),
        ]);
    }
}
