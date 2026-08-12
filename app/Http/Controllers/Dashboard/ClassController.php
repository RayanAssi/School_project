<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClassController extends Controller
{
    /**
     * عرض قائمة جميع الصفوف مع الشعب وعدد الأساتذة
     * GET /api/classes
     */
    public function index()
{
    try {
        $classes = Classes::with([
            'sections' => function ($query) {
                $query->select('id', 'name', 'class_id')
                    ->withCount(['teachers']); // ✅ عدد الأساتذة في كل شعبة
            },
            'students:id,user_id,class_id'
        ])
        ->withCount([
            'sections',      // ✅ عدد الشعب
            'students'       // ✅ عدد الطلاب
            // ❌ حذف 'sections.teachers' لأنها غير مدعومة
        ])
        ->orderBy('created_at', 'desc')
        ->paginate(15);

        // ✅ حساب عدد الأساتذة يدوياً
        foreach ($classes as $class) {
            $class->total_teachers = $class->sections->sum('teachers_count');
        }

        return response()->json([
            'success' => true,
            'data' => $classes,
            'message' => 'تم جلب الصفوف بنجاح'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ في جلب الصفوف',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * إنشاء صف جديد
     * POST /api/classes
     */
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول أولاً'
            ], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:classes,name',
            'comment' => 'nullable|string',
        ], [
            'name.required' => 'اسم الصف مطلوب',
            'name.string' => 'اسم الصف يجب أن يكون نصاً',
            'name.max' => 'اسم الصف لا يتجاوز 255 حرف',
            'name.unique' => 'هذا الصف موجود مسبقاً',
            'comment.string' => 'التعليق يجب أن يكون نصاً'
        ]);

        try {
            $class = Classes::create($validated);

            return response()->json([
                'success' => true,
                'data' => $class,
                'message' => 'تم إنشاء الصف بنجاح'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الصف',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض صف محدد مع عدد الشعب والطلاب والمدرسين والمواد
     * GET /api/classes/{id}
     */
    public function show($id)
    {
        try {
            // ✅ جلب الصف مع جميع العلاقات
            $class = Classes::with([
                'sections' => function ($query) {
                    $query->withCount(['teachers']); // عدد الأساتذة في كل شعبة
                },
                'subjects',
                'students' => function ($query) {
                    $query->with(['user:id,full_name,email', 'section:id,name']);
                }
            ])
                ->withCount(['students', 'subjects', 'sections'])
                ->find($id);

            if (!$class) {
                return response()->json([
                    'success' => false,
                    'message' => 'الصف غير موجود'
                ], 404);
            }

            // ✅ حساب عدد الأساتذة من خلال الشعب
            $totalTeachers = $class->sections->sum('teachers_count');

            return response()->json([
                'success' => true,
                'data' => [
                    'class' => $class,
                    'statistics' => [
                        'total_students' => $class->students_count,
                        'total_sections' => $class->sections_count,
                        'total_subjects' => $class->subjects_count,
                        'total_teachers' => $totalTeachers,
                    ]
                ],
                'message' => 'تم جلب بيانات الصف بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب بيانات الصف',
                'error' => $e->getMessage()
            ], 500);
        }
    }    /**
         * تحديث صف
         * PUT /api/classes/{id}
         */
    public function update(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول أولاً'
            ], 401);
        }

        $class = Classes::find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'الصف غير موجود'
            ], 404);
        }

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('classes', 'name')->ignore($class->id)
            ],
            'comment' => 'nullable|string',
        ], [
            'name.required' => 'اسم الصف مطلوب',
            'name.string' => 'اسم الصف يجب أن يكون نصاً',
            'name.max' => 'اسم الصف لا يتجاوز 255 حرف',
            'name.unique' => 'هذا الصف موجود مسبقاً'
        ]);

        try {
            $class->update($validated);

            return response()->json([
                'success' => true,
                'data' => $class,
                'message' => 'تم تحديث الصف بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الصف',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف صف
     * DELETE /api/classes/{id}
     */
    public function destroy($id)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول أولاً'
            ], 401);
        }

        $class = Classes::find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'الصف غير موجود'
            ], 404);
        }

        try {
            $class->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الصف بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الصف',
                'error' => $e->getMessage()
            ], 500);
        }
    }



}