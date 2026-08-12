<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Models\Parente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Student::with(['user', 'parent', 'class', 'section', 'subjects']);

            // فلترة حسب الصف
            if ($request->has('class_id') && $request->class_id) {
                $query->where('class_id', $request->class_id);
            }

            // فلترة حسب الشعبة
            if ($request->has('section_id') && $request->section_id) {
                $query->where('section_id', $request->section_id);
            }

            $students = $query->get();

            // تنسيق البيانات لعرض معلومات الطالب مع اسم الأب والأم
            $formattedStudents = $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'user_name' => $student->user->user_name ?? null,
                    'email' => $student->user->email ?? null,
                    'full_name' => $student->user->full_name ?? null,
                    'birth_date' => $student->birth_date,
                    'gender' => $student->gender,
                    'residential_address' => $student->residential_address,
                    'city' => $student->city,
                    'comment' => $student->comment,
                    'class_name' => $student->class->name ?? null,
                    'section_name' => $student->section->name ?? null,
                    'father_name' => $student->parent->full_name_father ?? null,
                    'mother_name' => $student->parent->full_name_mother ?? null,
                    'father_phone' => $student->parent->phone_number_father ?? null,
                    'mother_phone' => $student->parent->phone_number_mother ?? null,
                    'subjects' => $student->subjects->map(function ($subject) {
                        return [
                            'subject_name' => $subject->subject_name,
                            'mark' => $subject->pivot->mark,
                            'exam_type' => $subject->pivot->exam_type,
                            'date' => $subject->pivot->date,
                            'note' => $subject->pivot->note,
                        ];
                    }),
                    'created_at' => $student->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedStudents,
                'total' => $formattedStudents->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات الطلاب',
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
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'birth_date' => 'required|date',
            'gender' => 'required|in:ذكر,أنثى',
            'residential_address' => 'required|string',
            'city' => 'required|string|max:255',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'parent_id' => 'required|exists:parents,id', // الولي موجود مسبقاً
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        // توليد اسم المستخدم وكلمة المرور
        $userName = User::generateUserName('student', $request->full_name);
        $newPassword = User::generatePassword();

        // إنشاء حساب المستخدم للطالب فقط
        $user = User::create([
            'full_name' => $request->full_name,
            'user_name' => $userName,
            'email' => $request->email,
            'password' => Hash::make($newPassword),
            'user_type' => 'student',
        ]);

        // إنشاء بيانات الطالب وربطه بالولي الموجود
        $student = Student::create([
            'birth_date' => $request->birth_date,
            'comment' => $request->comment,
            'gender' => $request->gender,
            'residential_address' => $request->residential_address,
            'city' => $request->city,
            'parent_id' => $request->parent_id, // استخدام الولي الموجود
            'section_id' => $request->section_id,
            'class_id' => $request->class_id,
            'user_id' => $user->id,
        ]);

        DB::commit();

        // جلب بيانات الولي لعرضها في الرد
        $parent = Parente::with('user')->find($request->parent_id);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الطالب بنجاح',
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'full_name' => $user->full_name,
                    'user_name' => $userName,
                    'email' => $user->email,
                    'password' => $newPassword,
                    'birth_date' => $student->birth_date,
                    'gender' => $student->gender,
                    'residential_address' => $student->residential_address,
                    'city' => $student->city,
                    'class_id' => $student->class_id,
                    'section_id' => $student->section_id,
                    'comment' => $student->comment,
                ],
                'parent' => [
                    'id' => $parent->id,
                    'father_name' => $parent->full_name_father,
                    'mother_name' => $parent->full_name_mother,
                    'father_phone' => $parent->phone_number_father,
                    'mother_phone' => $parent->phone_number_mother,
                    'user_name' => $parent->user->user_name ?? null,
                ]
            ]
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء إنشاء الطالب',
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
            $student = Student::with(['user', 'parent', 'class', 'section', 'subjects'])->find($id);

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطالب غير موجود'
                ], 404);
            }

            $studentData = [
                'id' => $student->id,
                'user_name' => $student->user->user_name ?? null,
                'email' => $student->user->email ?? null,
                'full_name' => $student->user->full_name ?? null,
                'birth_date' => $student->birth_date,
                'gender' => $student->gender,
                'residential_address' => $student->residential_address,
                'city' => $student->city,
                'comment' => $student->comment,
                'class' => [
                    'id' => $student->class->id ?? null,
                    'name' => $student->class->name ?? null
                ],
                'section' => [
                    'id' => $student->section->id ?? null,
                    'name' => $student->section->name ?? null
                ],
                'parent' => [
                    'father_name' => $student->parent->full_name_father ?? null,
                    'mother_name' => $student->parent->full_name_mother ?? null,
                    'father_phone' => $student->parent->phone_number_father ?? null,
                    'mother_phone' => $student->parent->phone_number_mother ?? null,
                ],
                'subjects' => $student->subjects->map(function ($subject) {
                    return [
                        'id' => $subject->id,
                        'name' => $subject->subject_name,
                        'mark' => $subject->pivot->mark,
                        'exam_type' => $subject->pivot->exam_type,
                        'date' => $subject->pivot->date,
                        'note' => $subject->pivot->note,
                        'duration' => $subject->pivot->duration,
                    ];
                }),
                'created_at' => $student->created_at,
                'updated_at' => $student->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $studentData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات الطالب',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    /**
 * Update the specified resource in storage.
 */
public function update(Request $request, string $id)
{
    try {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'الطالب غير موجود'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|string|max:255',
            'birth_date' => 'sometimes|date',
            'gender' => 'sometimes|in:ذكر,أنثى',
            'residential_address' => 'sometimes|string',
            'city' => 'sometimes|string|max:255',
            'class_id' => 'sometimes|exists:classes,id',
            'section_id' => 'sometimes|exists:sections,id',
            'parent_id' => 'sometimes|exists:parents,id', // إضافة إمكانية تغيير الولي
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        // تحديث بيانات المستخدم (الاسم فقط، بدون الإيميل)
        if ($request->has('full_name')) {
            $user = User::find($student->user_id);
            if ($user) {
                $user->full_name = $request->full_name;
                $user->save();
            }
        }

        // تحديث بيانات الطالب
        $student->update($request->only([
            'birth_date', 'comment', 'gender', 'residential_address',
            'city', 'section_id', 'class_id', 'parent_id'
        ]));

        DB::commit();

        $updatedStudent = Student::with(['user', 'parent', 'class', 'section'])->find($id);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات الطالب بنجاح',
            'data' => $updatedStudent
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء تحديث بيانات الطالب',
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
            $student = Student::find($id);

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطالب غير موجود'
                ], 404);
            }

            DB::beginTransaction();

            // حذف الطالب
            $student->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الطالب بنجاح'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الطالب',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على جميع الآباء (للاستخدام في dropdown عند إنشاء الطالب)
     */
    public function getParentsList()
    {
        try {
            $parents = Parente::with('user')->get()->map(function ($parent) {
                return [
                    'id' => $parent->id,
                    'father_name' => $parent->full_name_father,
                    'mother_name' => $parent->full_name_mother,
                    'father_phone' => $parent->phone_number_father,
                    'mother_phone' => $parent->phone_number_mother,
                    'user_name' => $parent->user->user_name ?? null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $parents
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات الآباء',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get students by class
     */
    public function getStudentsByClass($classId)
    {
        try {
            $students = Student::with(['user', 'parent', 'class', 'section'])
                ->where('class_id', $classId)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $students,
                'total' => $students->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الطلاب حسب الصف',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get students by section
     */
    public function getStudentsBySection($sectionId)
    {
        try {
            $students = Student::with(['user', 'parent', 'class', 'section'])
                ->where('section_id', $sectionId)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $students,
                'total' => $students->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الطلاب حسب الشعبة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get students statistics
     */
    public function statistics()
    {
        try {
            $totalStudents = Student::count();
            $maleStudents = Student::where('gender', 'ذكر')->count();
            $femaleStudents = Student::where('gender', 'أنثى')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $totalStudents,
                    'male' => $maleStudents,
                    'female' => $femaleStudents,
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
     * Search students
     */
    public function search(Request $request)
    {
        try {
            $query = Student::with(['user', 'parent', 'class', 'section']);

            if ($request->has('q') && $request->q) {
                $searchTerm = $request->q;
                $query->whereHas('user', function ($q) use ($searchTerm) {
                    $q->where('full_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('user_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                });
            }

            $students = $query->get();

            return response()->json([
                'success' => true,
                'data' => $students,
                'total' => $students->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء البحث عن الطلاب',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}