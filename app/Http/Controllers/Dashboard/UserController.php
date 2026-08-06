<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
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
