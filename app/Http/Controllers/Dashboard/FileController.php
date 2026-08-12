<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class FileController extends Controller
{
    /**
     * عرض قائمة الملفات حسب المادة
     * GET /api/files?subject_id=1
     */
    public function index(Request $request)
    {
        try {
            $query = File::with(['subject:id,name']);

            // فلتر حسب المادة
            if ($request->has('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }

            $files = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $files,
                'message' => 'تم جلب الملفات بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب الملفات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * رفع ملف جديد (للاستاذ فقط)
     * POST /api/files
     */
    public function store(Request $request)
    {
        // ✅ التحقق من تسجيل الدخول
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول أولاً'
            ], 401);
        }

        // ✅ التحقق من صلاحية الأستاذ (يجب أن يكون المستخدم من نوع teacher)
        $user = Auth::user();
        if ($user->type !== 'teacher' && $user->type !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لرفع الملفات. هذه الميزة مخصصة للأساتذة فقط.'
            ], 403);
        }

        // ✅ التحقق من البيانات
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'subject_id' => 'required|exists:subjects,id',
            'file' => 'required|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip,rar|max:10240', // 10MB كحد أقصى
        ], [
            'name.required' => 'اسم الملف مطلوب',
            'name.string' => 'اسم الملف يجب أن يكون نصاً',
            'name.max' => 'اسم الملف لا يتجاوز 255 حرف',
            'subject_id.required' => 'المادة مطلوبة',
            'subject_id.exists' => 'المادة غير موجودة',
            'file.required' => 'الملف مطلوب',
            'file.file' => 'يجب أن يكون ملفاً صالحاً',
            'file.mimes' => 'نوع الملف غير مدعوم. الأنواع المدعومة: pdf, doc, docx, ppt, pptx, xls, xlsx, txt, zip, rar',
            'file.max' => 'حجم الملف يجب أن لا يتجاوز 10 ميجابايت',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // ✅ رفع الملف
            $uploadedFile = $request->file('file');
            $originalName = $uploadedFile->getClientOriginalName();
            $extension = $uploadedFile->getClientOriginalExtension();
            
            // إنشاء اسم فريد للملف
            $fileName = time() . '_' . uniqid() . '.' . $extension;
            
            // تخزين الملف في مجلد 'uploads/files'
            $path = $uploadedFile->storeAs('uploads/files', $fileName, 'public');

            // ✅ إنشاء سجل في قاعدة البيانات
            $file = File::create([
                'name' => $request->name,
                'subject_id' => $request->subject_id,
                'file_path' => $path,
                'file_name' => $originalName,
                'file_size' => $uploadedFile->getSize(),
                'file_extension' => $extension,
                'uploaded_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'file' => $file->load('subject'),
                    'download_url' => url('/api/files/' . $file->id . '/download')
                ],
                'message' => 'تم رفع الملف بنجاح'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء رفع الملف',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض ملف محدد
     * GET /api/files/{id}
     */
    public function show($id)
    {
        try {
            $file = File::with(['subject:id,name', 'uploader:id,full_name,email'])
                ->find($id);

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'الملف غير موجود'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $file,
                'message' => 'تم جلب بيانات الملف بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب بيانات الملف',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحميل الملف (للطلاب والأساتذة)
     * GET /api/files/{id}/download
     */
    public function download($id)
    {
        // ✅ التحقق من تسجيل الدخول
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول أولاً'
            ], 401);
        }

        try {
            $file = File::find($id);

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'الملف غير موجود'
                ], 404);
            }

            // ✅ التحقق من وجود الملف في التخزين
            if (!Storage::disk('public')->exists($file->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'الملف غير موجود في الخادم'
                ], 404);
            }

            // ✅ تسجيل عملية التحميل (اختياري)
            // يمكنك إنشاء جدول downloads لتسجيل من قام بالتحميل ومتى

            // ✅ تحميل الملف
            $filePath = Storage::disk('public')->path($file->file_path);
            
            return response()->download(
                $filePath, 
                $file->file_name ?? $file->name . '.' . $file->file_extension,
                [
                    'Content-Type' => $this->getMimeType($file->file_extension),
                ]
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل الملف',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث بيانات الملف (للاستاذ فقط)
     * PUT /api/files/{id}
     */
    public function update(Request $request, $id)
    {
        // ✅ التحقق من تسجيل الدخول
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول أولاً'
            ], 401);
        }

        // ✅ التحقق من صلاحية الأستاذ
        $user = Auth::user();
        if ($user->type !== 'teacher' && $user->type !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتحديث الملفات'
            ], 403);
        }

        $file = File::find($id);

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'الملف غير موجود'
            ], 404);
        }

        // ✅ التحقق من أن الملف مرفوع من هذا الأستاذ (أو هو Admin)
        if ($user->type !== 'admin' && $file->uploaded_by !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتعديل هذا الملف'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'subject_id' => 'sometimes|required|exists:subjects,id',
        ], [
            'name.required' => 'اسم الملف مطلوب',
            'name.string' => 'اسم الملف يجب أن يكون نصاً',
            'name.max' => 'اسم الملف لا يتجاوز 255 حرف',
            'subject_id.required' => 'المادة مطلوبة',
            'subject_id.exists' => 'المادة غير موجودة',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file->update($request->only(['name', 'subject_id']));

            return response()->json([
                'success' => true,
                'data' => $file->load('subject'),
                'message' => 'تم تحديث بيانات الملف بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الملف',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف ملف (للاستاذ فقط)
     * DELETE /api/files/{id}
     */
    public function destroy($id)
    {
        // ✅ التحقق من تسجيل الدخول
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول أولاً'
            ], 401);
        }

        // ✅ التحقق من صلاحية الأستاذ
        $user = Auth::user();
        if ($user->type !== 'teacher' && $user->type !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لحذف الملفات'
            ], 403);
        }

        $file = File::find($id);

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'الملف غير موجود'
            ], 404);
        }

        // ✅ التحقق من أن الملف مرفوع من هذا الأستاذ (أو هو Admin)
        if ($user->type !== 'admin' && $file->uploaded_by !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لحذف هذا الملف'
            ], 403);
        }

        try {
            // ✅ حذف الملف من التخزين
            if (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }

            // ✅ حذف السجل من قاعدة البيانات
            $file->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الملف بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الملف',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على ملفات مادة معينة
     * GET /api/files/subject/{subjectId}
     */
    public function getFilesBySubject($subjectId)
    {
        try {
            $subject = Subject::find($subjectId);

            if (!$subject) {
                return response()->json([
                    'success' => false,
                    'message' => 'المادة غير موجودة'
                ], 404);
            }

            $files = File::where('subject_id', $subjectId)
                ->with(['uploader:id,full_name'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => [
                    'subject' => $subject->only(['id', 'name']),
                    'files' => $files
                ],
                'message' => 'تم جلب ملفات المادة بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب ملفات المادة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على MIME type حسب امتداد الملف
     */
    private function getMimeType($extension)
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}