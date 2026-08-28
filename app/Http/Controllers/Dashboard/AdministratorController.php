<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Administrator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class AdministratorController extends Controller
{

    public function index(Request $request)
    {
        $query = Administrator::with('user');

        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        $administrators = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $administrators,
            'message' => 'تم جلب البيانات بنجاح'
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        //generate userName & password
        $userName = User::generateUserName('admin', $request->full_name);
        $password = User::generatePassword();

        $user = User::create([
            'user_name' => $userName,
            'full_name' => $request->full_name,
            'email' => $request->email,
            'password' => Hash::make($password),
            'user_type' => 'admin',
        ]);

        $admin = Administrator::create([
            'user_id' => $user->id,
            'role' => 'admin_assistant',
        ]);

        $responseData = [
            'success' => true,
            'message' => 'تم إنشاء المدير بنجاح',
            'role' => $admin->role,
            'user' =>[
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'user_name' => $userName,
                'password' => $password,
            ],
        ];
        return response()->json($responseData, 201);
    }
    public function update(Request $request, $id)
    {
        $admin = Administrator::find($id);
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'الادمن غير موجود'
            ], 404);
        }
        $user = User::find($admin->user_id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم المرتبط بالادمن غير موجود'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = [];

        if ($request->has('full_name')) {
            $updateData['full_name'] = $request->full_name;

            $newUserName = User::generateUserName("admin", $user->full_name);
            $user->user_name = $newUserName;
            $user->save();
        }
        if ($request->has('full_name')) {
        }
        if ($request->has('email')) {
            $updateData['email'] = $request->email;
        }
        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المستخدم بنجاح',
            'user' => $user->fresh(),
            'admin' => $admin->fresh(),
        ], 200);
    }

    public function destroy($id)
    {
        $admin = Administrator::find($id);
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'الادمن غير موجود'
            ], 404);
        }
        $user = User::find($admin->user_id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم المرتبط بالادمن غير موجود'
            ], 404);
        }
        if (Auth::id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك حذف حسابك الخاص'
            ], 403);
        }
        $admin->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المستخدم والادمن بنجاح'
        ], 200);
    }
    //generate userName
    public function resetUserName($id)
    {
        $admin = Administrator::find($id);
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'الادمن غير موجود'
            ], 404);
        }

        $user = User::find($admin->user_id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم المرتبط بالادمن غير موجود'
            ], 404);
        }

        $newUserName = User::generateUserName("admin", $user->full_name);
        $user->user_name = $newUserName;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة تعيين اسم المستخدم بنجاح',
            'new_user_name' => $newUserName,
            'user_id' => $user->id,
            'admin_id' => $admin->id
        ]);
    }
    // generate password
    public function resetPassword($id)
    {
        $admin = Administrator::find($id);
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'الادمن غير موجود'
            ], 404);
        }

        $user = User::find($admin->user_id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم المرتبط بالادمن غير موجود'
            ], 404);
        }

        $newPassword = User::generatePassword();
        $user->password = Hash::make($newPassword);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة تعيين كلمة المرور بنجاح',
            'new_password' => $newPassword,
            'user_id' => $user->id,
            'admin_id' => $admin->id

        ]);
    }
}
