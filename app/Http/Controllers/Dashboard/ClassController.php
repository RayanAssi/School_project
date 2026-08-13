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
     * Display a list of all classes with sections and teacher count
     * GET /api/classes
     */
    /**
 * Display a list of all classes with sections and teacher count
 * GET /api/classes
 */
/**
 * Display a list of all classes with sections, students, teachers and subjects
 * GET /api/classes
 */
public function index()
{
    try {
        $classes = Classes::with([
            'sections' => function ($query) {
                $query->with(['teachers' => function ($q) {
                    $q->with(['user:id,full_name,email']);
                }])
                ->withCount(['teachers']);
            },
            'subjects',
            'students' => function ($query) {
                $query->with(['user:id,full_name,email', 'section:id,name']);
            }
        ])
        ->withCount(['students', 'subjects', 'sections'])
        ->orderBy('created_at', 'desc')
        ->get();

        // تنسيق البيانات لكل صف
        $formattedClasses = $classes->map(function ($class) {
            // حساب عدد الأساتذة الكلي
            $totalTeachers = $class->sections->sum('teachers_count');

            // جلب قائمة الأساتذة مع تفاصيلهم
            $teachers = collect();
            foreach ($class->sections as $section) {
                foreach ($section->teachers as $teacher) {
                    $teachers->push([
                        'id' => $teacher->id,
                        'full_name' => $teacher->user->full_name ?? null,
                        'email' => $teacher->user->email ?? null,
                        'gender' => $teacher->gender ?? null,
                        'phone_number' => $teacher->phone_number ?? null,
                        'role' => $teacher->role ?? null,
                        'section_name' => $section->name,
                    ]);
                }
            }

            return [
                'id' => $class->id,
                'name' => $class->name,
                'comment' => $class->comment,
                'created_at' => $class->created_at,
                'updated_at' => $class->updated_at,
                'statistics' => [
                    'total_students' => $class->students_count,
                    'total_sections' => $class->sections_count,
                    'total_subjects' => $class->subjects_count,
                    'total_teachers' => $totalTeachers,
                ],
                'sections' => $class->sections->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'name' => $section->name,
                        'teachers_count' => $section->teachers_count,
                    ];
                }),
                'subjects' => $class->subjects->map(function ($subject) {
                    return [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'full_mark' => $subject->full_mark,
                        'comment' => $subject->comment,
                    ];
                }),
                'students' => $class->students->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'full_name' => $student->user->full_name ?? null,
                        'email' => $student->user->email ?? null,
                        'section_name' => $student->section->name ?? null,
                    ];
                }),
                'teachers' => $teachers,
                'teachers_count' => $teachers->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedClasses,
            'total' => $formattedClasses->count(),
            'message' => 'تم جلب جميع الصفوف بنجاح'
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
     * Create a new class
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
     * Display a specific class with sections, students, teachers and subjects
     * GET /api/classes/{id}
     */
    /**
 * Display a specific class with sections, students, teachers and subjects
 * GET /api/classes/{id}
 */
public function show($id)
{
    try {
        $class = Classes::with([
            'sections' => function ($query) {
                $query->withCount(['teachers']);
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

        // حساب عدد الأساتذة الكلي
        $totalTeachers = $class->sections->sum('teachers_count');

        // جلب قائمة الأساتذة مع تفاصيلهم
        $teachers = collect();
        foreach ($class->sections as $section) {
            foreach ($section->teachers as $teacher) {
                $teachers->push([
                    'id' => $teacher->id,
                    'full_name' => $teacher->user->full_name ?? null,
                    'email' => $teacher->user->email ?? null,
                    'gender' => $teacher->gender ?? null,
                    'phone_number' => $teacher->phone_number ?? null,
                    'role' => $teacher->role ?? null,
                    'section_name' => $section->name,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'class' => $class,
                'statistics' => [
                    'total_students' => $class->students_count,
                    'total_sections' => $class->sections_count,
                    'total_subjects' => $class->subjects_count,
                    'total_teachers' => $totalTeachers,
                ],
                'teachers' => $teachers, // قائمة الأساتذة
                'teachers_count' => $teachers->count(), // عدد الأساتذة
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
}

    /**
     * Update a class
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
     * Delete a class
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