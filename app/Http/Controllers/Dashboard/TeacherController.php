<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $teachers = Teacher::with(['user', 'subjects', 'sections'])->get();
        return response()->json([
            'status' => true,
            'message' => 'All teachers retrieved successfully',
            'data' => $teachers,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gender' => 'required|in:ذكر,أنثى',
            'comment' => 'nullable|string',
            'role' => 'required|in:مدرس,موجه',
            'phone_number' => 'required|string|unique:teachers,phone_number',
            'user_id' => 'required|exists:users,id',
            'subjects' => 'nullable|array',
            'subjects.*' => 'exists:subjects,id',
            'sections' => 'nullable|array',
            'sections.*' => 'exists:sections,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // إنشاء المعلم
        $teacher = Teacher::create($request->only([
            'gender',
            'comment',
            'role',
            'phone_number',
            'user_id',
        ]));

        // ربط المواد (إن وجدت)
        if ($request->has('subjects')) {
            $teacher->subjects()->sync($request->subjects);
        }

        // ربط الشعب (إن وجدت)
        if ($request->has('sections')) {
            $teacher->sections()->sync($request->sections);
        }

        return response()->json([
            'status' => true,
            'message' => 'Teacher created successfully',
            'data' => $teacher->load(['user', 'subjects', 'sections']),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $teacher = Teacher::with(['user', 'subjects', 'sections'])->find($id);

        if (!$teacher) {
            return response()->json([
                'status' => false,
                'message' => 'Teacher not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Teacher retrieved successfully',
            'data' => $teacher,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json([
                'status' => false,
                'message' => 'Teacher not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'gender' => 'sometimes|required|in:ذكر,أنثى',
            'comment' => 'nullable|string',
            'role' => 'sometimes|required|in:مدرس,موجه',
            'phone_number' => 'sometimes|required|string|unique:teachers,phone_number,' . $id,
            'user_id' => 'sometimes|required|exists:users,id',
            'subjects' => 'nullable|array',
            'subjects.*' => 'exists:subjects,id',
            'sections' => 'nullable|array',
            'sections.*' => 'exists:sections,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // تحديث بيانات المعلم
        $teacher->update($request->only([
            'gender',
            'comment',
            'role',
            'phone_number',
            'user_id',
        ]));

        // تحديث المواد (إن وجدت)
        if ($request->has('subjects')) {
            $teacher->subjects()->sync($request->subjects);
        }

        // تحديث الشعب (إن وجدت)
        if ($request->has('sections')) {
            $teacher->sections()->sync($request->sections);
        }

        return response()->json([
            'status' => true,
            'message' => 'Teacher updated successfully',
            'data' => $teacher->load(['user', 'subjects', 'sections']),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json([
                'status' => false,
                'message' => 'Teacher not found',
            ], 404);
        }

        // حذف العلاقات أولاً (Many-to-Many)
        $teacher->subjects()->detach();
        $teacher->sections()->detach();

        // حذف المعلم
        $teacher->delete();

        return response()->json([
            'status' => true,
            'message' => 'Teacher deleted successfully',
        ], 200);
    }

    /**
     * Get teachers by role (مدرس / موجه)
     */
    public function getByRole($role)
    {
        $teachers = Teacher::with(['user', 'subjects', 'sections'])
            ->where('role', $role)
            ->get();

        return response()->json([
            'status' => true,
            'message' => "Teachers with role '{$role}' retrieved successfully",
            'data' => $teachers,
        ], 200);
    }

    /**
     * Get teachers by gender (ذكر / أنثى)
     */
    public function getByGender($gender)
    {
        $teachers = Teacher::with(['user', 'subjects', 'sections'])
            ->where('gender', $gender)
            ->get();

        return response()->json([
            'status' => true,
            'message' => "Teachers with gender '{$gender}' retrieved successfully",
            'data' => $teachers,
        ], 200);
    }
}
