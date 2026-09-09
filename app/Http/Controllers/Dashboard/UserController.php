<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Parente;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    //show all users 
    public function index()
    {
        $users = User::paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب المستخدمين بنجاح',
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'next_page_url' => $users->nextPageUrl(),
                'prev_page_url' => $users->previousPageUrl(),
            ]
        ]);
    }

    //show the authenticated user information
    // public function show()
    // {

    //     return response()->json([
    //         'user' => Auth::user(),
    //     ]);

    // }

public function show()
{
    $user = Auth::user();

    $userData = [
        'id' => $user->id,
        'user_name' => $user->user_name,
        'full_name' => $user->full_name,
        'email' => $user->email,
        'user_type' => $user->user_type,
        'created_at' => $user->created_at,
        'updated_at' => $user->updated_at,
    ];

    if ($user->user_type === 'student') {
        $student = Student::where('user_id', $user->id)->first();
        if ($student) {
            $userData['student_id'] = $student->id;
        }
    }

    if ($user->user_type === 'parent') {
        $parent = Parente::where('user_id', $user->id)->first();
        
        if ($parent) {
            $students = Student::with(['user', 'class', 'section'])
                ->where('parent_id', $parent->id)
                ->get();
            
            // ✅ التعديل هنا ✅
            $childrenIds = $students->pluck('id')->toArray(); // [1, 2, 3]
            
            $userData['parent_id'] = $parent->id;
            $userData['father_name'] = $parent->full_name_father;
            $userData['children_ids'] = $childrenIds; // ✅ السطر الجديد
            $userData['students'] = $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'full_name' => $student->user->full_name ?? null,
                    'user_name' => $student->user->user_name ?? null,
                    'email' => $student->user->email ?? null,
                    'birth_date' => $student->birth_date,
                    'gender' => $student->gender,
                    'class_id' => $student->class_id,
                    'class_name' => $student->class->name ?? null,
                    'section_id' => $student->section_id,
                    'section_name' => $student->section->name ?? null,
                    'residential_address' => $student->residential_address,
                    'city' => $student->city,
                    'comment' => $student->comment,
                ];
            });
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'تم جلب بيانات المستخدم بنجاح',
        'data' => $userData
    ]);
}


    //regenerate username 
    public function resetUserName($id)
    {
        try {
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

    //reset password for a user
    public function resetPassword($id)
    {
        try {
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
