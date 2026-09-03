<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Teacher::with(['user']);

            //filter by gender 
            if ($request->has('gender') && $request->gender) {
                $query->where('gender', $request->gender);
            }

            $teachers = $query->get();

            $formattedTeachers = $teachers->map(function ($teacher) {
                return [
                    'id' => $teacher->id,
                    'user_name' => $teacher->user->user_name ?? null,
                    'email' => $teacher->user->email ?? null,
                    'full_name' => $teacher->user->full_name ?? null,
                    'gender' => $teacher->gender,
                    'phone_number' => $teacher->phone_number,
                    'comment' => $teacher->comment,
                    'created_at' => $teacher->created_at,
                    'updated_at' => $teacher->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedTeachers,
                'total' => $formattedTeachers->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات المدرسين',
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
            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'gender' => 'required|in:ذكر,أنثى',
                'phone_number' => 'required|string|unique:teachers,phone_number',
                'comment' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $userName = User::generateUserName('teacher', $request->full_name);
            $newPassword = User::generatePassword();

            // create user
            $user = User::create([
                'full_name' => $request->full_name,
                'user_name' => $userName,
                'email' => $request->email,
                'password' => Hash::make($newPassword),
                'user_type' => 'teacher',
            ]);

            // create teacher
            $teacher = Teacher::create([
                'gender' => $request->gender,
                'comment' => $request->comment,
                'phone_number' => $request->phone_number,
                'user_id' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء المدرس بنجاح',
                'data' => [
                    'teacher' => [
                        'id' => $teacher->id,
                        'full_name' => $user->full_name,
                        'user_name' => $userName,
                        'email' => $user->email,
                        'password' => $newPassword,
                        'gender' => $teacher->gender,
                        'phone_number' => $teacher->phone_number,
                        'comment' => $teacher->comment,
                    ]
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء المدرس',
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
            $teacher = Teacher::with(['user'])->find($id);

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'المدرس غير موجود'
                ], 404);
            }

            $teacherData = [
                'id' => $teacher->id,
                'user_name' => $teacher->user->user_name ?? null,
                'email' => $teacher->user->email ?? null,
                'full_name' => $teacher->user->full_name ?? null,
                'gender' => $teacher->gender,
                'phone_number' => $teacher->phone_number,
                'comment' => $teacher->comment,
                'created_at' => $teacher->created_at,
                'updated_at' => $teacher->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $teacherData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات المدرس',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $teacher = Teacher::find($id);

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'المدرس غير موجود'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'full_name' => 'sometimes|string|max:255',
                'gender' => 'sometimes|in:ذكر,أنثى',
                'phone_number' => 'sometimes|string|unique:teachers,phone_number,' . $id,
                'comment' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // تحديث بيانات المستخدم (الاسم فقط)
            if ($request->has('full_name')) {
                $user = User::find($teacher->user_id);
                if ($user) {
                    $user->full_name = $request->full_name;
                    $user->save();
                }
            }

            // تحديث بيانات المدرس
            $teacher->update($request->only([
                'gender',
                'phone_number',
                'comment'
            ]));

            DB::commit();

            $updatedTeacher = Teacher::with(['user'])->find($id);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات المدرس بنجاح',
                'data' => $updatedTeacher
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث بيانات المدرس',
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
            $teacher = Teacher::with('user')->find($id);

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'المدرس غير موجود'
                ], 404);
            }

            DB::beginTransaction();

            // حذف المستخدم المرتبط
            if ($teacher->user) {
                $teacher->user->delete();
            }

            // حذف المدرس
            $teacher->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المدرس والمستخدم المرتبط به بنجاح'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المدرس',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get teachers statistics
     */
    public function statistics()
    {
        try {
            $totalTeachers = Teacher::count();
            $maleTeachers = Teacher::where('gender', 'ذكر')->count();
            $femaleTeachers = Teacher::where('gender', 'أنثى')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $totalTeachers,
                    'male' => $maleTeachers,
                    'female' => $femaleTeachers,
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
     * Search teachers
     */
    public function search(Request $request)
    {
        try {
            $query = Teacher::with(['user']);

            if ($request->has('q') && $request->q) {
                $searchTerm = $request->q;
                $query->whereHas('user', function ($q) use ($searchTerm) {
                    $q->where('full_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('user_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                })->orWhere('phone_number', 'LIKE', "%{$searchTerm}%");
            }

            $teachers = $query->get();

            return response()->json([
                'success' => true,
                'data' => $teachers,
                'total' => $teachers->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء البحث عن المدرسين',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get teachers by gender
     */
    public function getTeachersByGender($gender)
    {
        try {
            $teachers = Teacher::with(['user'])
                ->where('gender', $gender)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $teachers,
                'total' => $teachers->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المدرسين حسب الجنس',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // app/Http/Controllers/Dashboard/TeacherController.php

public function resetPassword($id)
{
    try {
        $teacher = Teacher::with('user')->find($id);
        
        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'المدرس غير موجود'
            ], 404);
        }
        
        $user = $teacher->user;
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


 /**
     * Get teacher's classes with sections and their subjects
     */
    public function getClasses($id)
    {
        try {
            $teacher = Teacher::with(['sections.class', 'subjects'])->find($id);
            
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'المدرس غير موجود'
                ], 404);
            }
            
            // تجميع الصفوف مع شعبها وموادها
            $classes = [];
            $seenClasses = [];
            
            foreach ($teacher->sections as $section) {
                $classId = $section->class_id;
                $className = $section->class->name ?? 'بدون صف';
                
                if (!isset($seenClasses[$classId])) {
                    $seenClasses[$classId] = true;
                    $classes[] = [
                        'id' => $classId,
                        'class_name' => $className,
                        'sections' => []
                    ];
                }
                
                // جلب المواد لهذه الشعبة
                $sectionSubjects = [];
                foreach ($teacher->subjects as $subject) {
                    // التحقق إذا كانت هذه المادة مرتبطة بهذه الشعبة
                    $isRelated = $subject->sections()->where('sections.id', $section->id)->exists();
                    if ($isRelated) {
                        $sectionSubjects[] = [
                            'id' => $subject->id,
                            'name' => $subject->name ?? $subject->subject_name ?? 'بدون مادة'
                        ];
                    }
                }
                
                // إضافة الشعبة للصف المناسب
                foreach ($classes as &$class) {
                    if ($class['id'] === $classId) {
                        $class['sections'][] = [
                            'id' => $section->id,
                            'name' => $section->name ?? 'بدون شعبة',
                            'subjects' => $sectionSubjects
                        ];
                        break;
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $classes
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب صفوف المدرس',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get teacher's subjects (simplified)
     */
    public function getSubjects($id)
    {
        try {
            $teacher = Teacher::with(['subjects'])->find($id);
            
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'المدرس غير موجود'
                ], 404);
            }
            
            $subjects = $teacher->subjects->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->name ?? $subject->subject_name ?? 'بدون مادة',
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $subjects
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب مواد المدرس',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
