<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    /**
     * عرض قائمة جميع المعلمين
     */
    public function index(Request $request)
    {
        $query = Teacher::with('user');

        // فلترة حسب الجنس
        if ($request->has('gender') && $request->gender) {
            $query->where('gender', $request->gender);
        }

        // فلترة حسب الدور
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        // بحث حسب رقم الهاتف
        if ($request->has('search') && $request->search) {
            $query->where('phone_number', 'LIKE', '%' . $request->search . '%');
        }

        $teachers = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $teachers,
            'message' => 'تم جلب البيانات بنجاح'
        ]);
    }

    /**
     * إنشاء معلم جديد
     */
    public function store(Request $request)
    {
        // التحقق من البيانات
        $validator = validator($request->all(), [
            'gender' => 'required|in:ذكر,أنثى',
            'comment' => 'nullable|string|max:255',
            'phone_number' => 'required|string|max:20|unique:teachers,phone_number',
            'role' => 'required|in:مدرس,موجه',
            'user_id' => 'required|exists:users,id'
        ], [
            'gender.required' => 'حقل الجنس مطلوب',
            'gender.in' => 'الجنس يجب أن يكون ذكر أو أنثى',
            'phone_number.required' => 'رقم الهاتف مطلوب',
            'phone_number.unique' => 'رقم الهاتف مستخدم من قبل',
            'role.required' => 'حقل الدور مطلوب',
            'role.in' => 'الدور يجب أن يكون مدرس أو موجه',
            'user_id.required' => 'المستخدم مطلوب',
            'user_id.exists' => 'المستخدم غير موجود'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $teacher = Teacher::create($validator->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $teacher->load('user'),
                'message' => 'تم إضافة المعلم بنجاح'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة المعلم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض بيانات معلم محدد
     */
    public function show($id)
    {
        $teacher = Teacher::with('user')->find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'المعلم غير موجود'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $teacher,
            'message' => 'تم جلب بيانات المعلم بنجاح'
        ]);
    }

    /**
     * تحديث بيانات معلم
     */
    public function update(Request $request, $id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'المعلم غير موجود'
            ], 404);
        }

        // التحقق من البيانات
        $validator = validator($request->all(), [
            'gender' => 'sometimes|in:ذكر,أنثى',
            'comment' => 'nullable|string|max:255',
            'phone_number' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('teachers', 'phone_number')->ignore($id)
            ],
            'role' => 'sometimes|in:مدرس,موجه',
            'user_id' => 'sometimes|exists:users,id'
        ], [
            'gender.in' => 'الجنس يجب أن يكون ذكر أو أنثى',
            'phone_number.unique' => 'رقم الهاتف مستخدم من قبل',
            'role.in' => 'الدور يجب أن يكون مدرس أو موجه',
            'user_id.exists' => 'المستخدم غير موجود'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $teacher->update($validator->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $teacher->fresh()->load('user'),
                'message' => 'تم تحديث بيانات المعلم بنجاح'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث المعلم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف معلم
     */
    public function destroy($id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'المعلم غير موجود'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $teacher->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المعلم بنجاح'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المعلم',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * الحصول على إحصائيات المعلمين
     */
    public function statistics()
    {
        $stats = [
            'total' => Teacher::count(),
            'males' => Teacher::where('gender', 'ذكر')->count(),
            'females' => Teacher::where('gender', 'أنثى')->count(),
            'teachers' => Teacher::where('role', 'مدرس')->count(),
            'supervisors' => Teacher::where('role', 'موجه')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'تم جلب الإحصائيات بنجاح'
        ]);
    }

    public function search(Request $request)
    {
        $query = Teacher::with('user');

        if ($request->has('phone_number') && $request->phone_number) {
            $query->where('phone_number', 'LIKE', '%' . $request->phone_number . '%');
        }

        if ($request->has('gender') && $request->gender) {
            $query->where('gender', $request->gender);
        }

        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        if ($request->has('comment') && $request->comment) {
            $query->where('comment', 'LIKE', '%' . $request->comment . '%');
        }

        $teachers = $query->get();

        // ✅ التحقق: إذا ما في نتائج
        if ($teachers->isEmpty()) {
            return response()->json([
                'success' => false,
                'data' => [],
                'count' => 0,
                'message' => 'لا يوجد نتائج مطابقة للبحث'
            ], 404); // أو 200 حسب رغبتك
        }

        return response()->json([
            'success' => true,
            'count' => $teachers->count(),
            'data' => $teachers,
            'message' => 'تم جلب نتائج البحث بنجاح'
        ]);
    }
}
