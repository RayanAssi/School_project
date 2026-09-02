<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Parente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ParentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Parente::with(['user']);

            // فلترة حسب اسم الأب
            if ($request->has('father_name') && $request->father_name) {
                $query->where('full_name_father', 'LIKE', "%{$request->father_name}%");
            }

            // فلترة حسب اسم الأم
            if ($request->has('mother_name') && $request->mother_name) {
                $query->where('full_name_mother', 'LIKE', "%{$request->mother_name}%");
            }

            // فلترة حسب وظيفة الأب
            if ($request->has('job_father') && $request->job_father) {
                $query->where('job_father', 'LIKE', "%{$request->job_father}%");
            }

            // فلترة حسب وظيفة الأم
            if ($request->has('job_mother') && $request->job_mother) {
                $query->where('job_mother', 'LIKE', "%{$request->job_mother}%");
            }


            $parents = $query->get();

            $formattedParents = $parents->map(function ($parent) {
                return [
                    'id' => $parent->id,
                    'user_name' => $parent->user->user_name ?? null,
                    'email' => $parent->user->email ?? null,
                    'full_name_father' => $parent->full_name_father,
                    'full_name_mother' => $parent->full_name_mother,
                    'job_father' => $parent->job_father,
                    'job_mother' => $parent->job_mother,
                    'phone_number_father' => $parent->phone_number_father,
                    'phone_number_mother' => $parent->phone_number_mother,
                    'created_at' => $parent->created_at,
                    'updated_at' => $parent->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedParents,
                'total' => $formattedParents->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات الأولياء',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // التحقق من صحة البيانات
            $validator = Validator::make($request->all(), [
                'full_name_father' => 'required|string|max:255',
                'full_name_mother' => 'required|string|max:255',
                'job_father' => 'required|string|max:255',
                'job_mother' => 'required|string|max:255',
                'phone_number_father' => 'required|string|unique:parents,phone_number_father',
                'phone_number_mother' => 'required|string|unique:parents,phone_number_mother',
                'email' => 'required|email|unique:users,email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $userName = User::generateUserName('parent', $request->full_name_father);
            $newPassword = User::generatePassword();

            //create user
            $user = User::create([
                'full_name' => $request->full_name_father,
                'user_name' => $userName,
                'email' => $request->email,
                'password' => Hash::make($newPassword),
                'user_type' => 'parent',
            ]);

            //create parent
            $parent = Parente::create([
                'full_name_father' => $request->full_name_father,
                'full_name_mother' => $request->full_name_mother,
                'job_father' => $request->job_father,
                'job_mother' => $request->job_mother,
                'phone_number_father' => $request->phone_number_father,
                'phone_number_mother' => $request->phone_number_mother,
                'user_id' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الأب بنجاح',
                'data' => [
                    'parent' => [
                        'id' => $parent->id,
                        'full_name_father' => $parent->full_name_father,
                        'full_name_mother' => $parent->full_name_mother,
                        'job_father' => $parent->job_father,
                        'job_mother' => $parent->job_mother,
                        'phone_number_father' => $parent->phone_number_father,
                        'phone_number_mother' => $parent->phone_number_mother,
                        'user_name' => $userName,
                        'email' => $user->email,
                        'password' => $newPassword,
                    ]
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الأب',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $parent = Parente::with(['user'])->find($id);

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'الأب غير موجود'
                ], 404);
            }

            $parentData = [
                'id' => $parent->id,
                'user_name' => $parent->user->user_name ?? null,
                'email' => $parent->user->email ?? null,
                'full_name_father' => $parent->full_name_father,
                'full_name_mother' => $parent->full_name_mother,
                'job_father' => $parent->job_father,
                'job_mother' => $parent->job_mother,
                'phone_number_father' => $parent->phone_number_father,
                'phone_number_mother' => $parent->phone_number_mother,
                'created_at' => $parent->created_at,
                'updated_at' => $parent->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $parentData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات الأب',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
   // app/Http/Controllers/Dashboard/ParentController.php

public function update(Request $request, string $id)
{
    try {
        $parent = Parente::find($id);

        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'الأب غير موجود'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'full_name_father' => 'sometimes|string|max:255',
            'full_name_mother' => 'sometimes|string|max:255',
            'job_father' => 'sometimes|string|max:255',
            'job_mother' => 'sometimes|string|max:255',
            'phone_number_father' => 'sometimes|string|unique:parents,phone_number_father,' . $id,
            'phone_number_mother' => 'sometimes|string|unique:parents,phone_number_mother,' . $id,
            'email' => 'sometimes|email|unique:users,email,' . $parent->user_id,
            'user_name' => 'sometimes|string|unique:users,user_name,' . $parent->user_id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        $user = User::find($parent->user_id);
        if ($user) {
            if ($request->has('full_name_father')) {
                $user->full_name = $request->full_name_father;
            }

            if ($request->has('user_name')) {
                $user->user_name = $request->user_name;
            }

            if ($request->has('email')) {
                $user->email = $request->email;
            }

            $user->save();
        }

        // تحديث بيانات ولي الأمر
        $parent->update($request->only([
            'full_name_father',
            'full_name_mother',
            'job_father',
            'job_mother',
            'phone_number_father',
            'phone_number_mother'
        ]));

        DB::commit();

        $updatedParent = Parente::with(['user'])->find($id);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات الأب بنجاح',
            'data' => $updatedParent
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء تحديث بيانات الأب',
            'error' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $parent = Parente::find($id);

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'الأب غير موجود'
                ], 404);
            }

            DB::beginTransaction();

            // delete the associated user
            $user = User::find($parent->user_id);
            if ($user) {
                $user->delete();
            }

            // delete the parent 
            $parent->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الأب والمستخدم المرتبط به بنجاح'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الأب',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get parents statistics
     */
    public function statistics()
    {
        try {
            $totalParents = Parente::count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $totalParents,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search parents
     */
    /**
     * Search parents
     */
    public function search(Request $request)
    {
        try {
            $query = Parente::with(['user']);

            // دعم القيم من GET و POST
            $searchTerm = $request->input('q') ?? $request->query('q');

            if ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('full_name_father', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('full_name_mother', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('job_father', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('job_mother', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('phone_number_father', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('phone_number_mother', 'LIKE', "%{$searchTerm}%")
                        ->orWhereHas('user', function ($u) use ($searchTerm) {
                            $u->where('full_name', 'LIKE', "%{$searchTerm}%")
                                ->orWhere('user_name', 'LIKE', "%{$searchTerm}%")
                                ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                        });
                });
            }

            $parents = $query->get();

            return response()->json([
                'success' => true,
                'data' => $parents,
                'total' => $parents->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء البحث عن الأولياء',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get parents by phone number (father)
     */
    public function getParentsByFatherPhone($phone)
    {
        try {
            $parents = Parente::with(['user'])
                ->where('phone_number_father', 'LIKE', "%{$phone}%")
                ->get();

            return response()->json([
                'success' => true,
                'data' => $parents,
                'total' => $parents->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الأولياء حسب رقم هاتف الأب',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get parents by phone number (mother)
     */
    public function getParentsByMotherPhone($phone)
    {
        try {
            $parents = Parente::with(['user'])
                ->where('phone_number_mother', 'LIKE', "%{$phone}%")
                ->get();

            return response()->json([
                'success' => true,
                'data' => $parents,
                'total' => $parents->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الأولياء حسب رقم هاتف الأم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get children of a specific parent
     */
    public function getChildren($parentId)
    {
        try {
            $parent = Parente::with(['students.user', 'students.class', 'students.section'])->find($parentId);

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'الولي غير موجود'
                ], 404);
            }

            // جلب أبناء الولي
            $children = $parent->students;

            // تنسيق بيانات الأبناء
            $formattedChildren = $children->map(function ($student) {
                return [
                    'id' => $student->id,
                    'full_name' => $student->user->full_name ?? null,
                    'user_name' => $student->user->user_name ?? null,
                    'email' => $student->user->email ?? null,
                    'birth_date' => $student->birth_date,
                    'gender' => $student->gender,
                    'residential_address' => $student->residential_address,
                    'city' => $student->city,
                    'class_name' => $student->class->name ?? null,
                    'section_name' => $student->section->name ?? null,
                    'comment' => $student->comment,
                    'created_at' => $student->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'parent' => [
                        'id' => $parent->id,
                        'father_name' => $parent->full_name_father,
                        'mother_name' => $parent->full_name_mother,
                        'phone_father' => $parent->phone_number_father,
                        'phone_mother' => $parent->phone_number_mother,
                    ],
                    'children' => $formattedChildren,
                    'total_children' => $formattedChildren->count()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب أبناء الولي',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password for a parent
     */
    public function resetPassword(Request $request, string $id)
    {
        try {
            $parent = Parente::find($id);

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'ولي الأمر غير موجود'
                ], 404);
            }

            $user = User::find($parent->user_id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود'
                ], 404);
            }

            // توليد كلمة مرور جديدة باستخدام دالة User
            $newPassword = User::generatePassword();
            $user->password = Hash::make($newPassword);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'تم إعادة تعيين كلمة المرور بنجاح',
                'data' => [
                    'user_name' => $user->user_name,
                    'password' => $newPassword
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إعادة تعيين كلمة المرور',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
