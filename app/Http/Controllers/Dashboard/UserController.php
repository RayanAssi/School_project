<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * عرض كل المستخدمين
     */
    public function index()
    {
        $users = User::paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب المستخدمين بنجاح',
            'data' => $users
        ]);
    }

    /**
     * عرض معلومات المستخدم الحالي 
     */
    public function show()
    {
        return response()->json([
            'user' => Auth::user(),
            'email' => Auth::user()->email,
            'user_type' => Auth::user()->user_type,
            'device_count' => count(Auth::user()->device_token ?? []),
        ]);
    }

    /**
     * إعادة تعيين اسم المستخدم (توليد تلقائي)
     * POST /api/dashboard/users/{id}/reset-username
     */
    public function resetUserName($id)
    {
        try {
            // التحقق من الصلاحية (admin فقط)
            if (Auth::user()->user_type !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بهذه العملية'
                ], 403);
            }

            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود'
                ], 404);
            }

            // توليد اسم مستخدم جديد حسب نوع المستخدم
            $newUserName = User::generateUserName($user->user_type, $user->full_name);
            $user->user_name = $newUserName;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'تم إعادة تعيين اسم المستخدم بنجاح',
                'data' => [
                    'new_user_name' => $newUserName,
                    'user_id' => $user->id,
                    'user_type' => $user->user_type,
                    'full_name' => $user->full_name,
                    'email' => $user->email
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إعادة تعيين اسم المستخدم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إعادة تعيين كلمة المرور (توليد تلقائي)
     * POST /api/dashboard/users/{id}/reset-password
     */
    public function resetPassword($id)
    {
        try {
            // التحقق من الصلاحية (admin فقط)
            if (Auth::user()->user_type !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بهذه العملية'
                ], 403);
            }

            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود'
                ], 404);
            }

            // توليد كلمة سر جديدة
            $newPassword = User::generatePassword();
            $user->password = Hash::make($newPassword);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'تم إعادة تعيين كلمة المرور بنجاح',
                'data' => [
                    'new_password' => $newPassword,
                    'user_id' => $user->id,
                    'user_type' => $user->user_type,
                    'user_name' => $user->user_name,
                    'full_name' => $user->full_name,
                    'email' => $user->email
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إعادة تعيين كلمة المرور',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
}