<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StudentSubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = StudentSubject::with(['student', 'subject']);

        // Filter by student
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        // Filter by subject
        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        // Filter by exam type
        if ($request->has('exam_type')) {
            $query->where('exam_type', $request->exam_type);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        // Filter by mark range
        if ($request->has('min_mark')) {
            $query->where('mark', '>=', $request->min_mark);
        }
        if ($request->has('max_mark')) {
            $query->where('mark', '<=', $request->max_mark);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $results = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
                'has_more_pages' => $results->hasMorePages(),
            ]
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // جلب المادة للتأكد من وجودها والحصول على full_mark
        $subject = Subject::find($request->subject_id);

        if (!$subject) {
            return response()->json([
                'status' => 'error',
                'message' => 'المادة غير موجودة'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'date' => 'required|date',
            'note' => 'nullable|string|max:500',
            'duration' => 'nullable|date_format:H:i:s',
            'exam_type' => ['required', Rule::in(['نصفي', 'نهائي'])],
            'mark' => [
                'nullable',
                'numeric',
                'min:0',
                'max:' . $subject->full_mark, // التحقق من أن العلامة لا تتجاوز full_mark
                function ($attribute, $value, $fail) use ($subject) {
                    if ($value !== null && $value > $subject->full_mark) {
                        $fail("العلامة لا يمكن أن تتجاوز " . $subject->full_mark . " درجة");
                    }
                }
            ]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for duplicate entry
        $exists = StudentSubject::where('student_id', $request->student_id)
            ->where('subject_id', $request->subject_id)
            ->where('exam_type', $request->exam_type)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'هذا الطالب مسجل بالفعل لهذه المادة ونوع الامتحان'
            ], 409);
        }

        $studentSubject = StudentSubject::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'تم إضافة التسجيل بنجاح',
            'data' => $studentSubject->load(['student', 'subject'])
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $studentSubject = StudentSubject::find($id);

        if (!$studentSubject) {
            return response()->json([
                'status' => 'error',
                'message' => 'التسجيل غير موجود'
            ], 404);
        }

        // جلب المادة للحصول على full_mark
        $subject = Subject::find($request->subject_id ?? $studentSubject->subject_id);

        if (!$subject) {
            return response()->json([
                'status' => 'error',
                'message' => 'المادة غير موجودة'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'sometimes|exists:students,id',
            'subject_id' => 'sometimes|exists:subjects,id',
            'date' => 'sometimes|date',
            'note' => 'nullable|string|max:500',
            'duration' => 'nullable|date_format:H:i:s',
            'exam_type' => ['sometimes', Rule::in(['نصفي', 'نهائي'])],
            'mark' => [
                'nullable',
                'numeric',
                'min:0',
                'max:' . $subject->full_mark,
                function ($attribute, $value, $fail) use ($subject) {
                    if ($value !== null && $value > $subject->full_mark) {
                        $fail("العلامة لا يمكن أن تتجاوز " . $subject->full_mark . " درجة");
                    }
                }
            ]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $studentSubject->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث التسجيل بنجاح',
            'data' => $studentSubject->load(['student', 'subject'])
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $studentSubject = StudentSubject::with(['student', 'subject'])->find($id);

        if (!$studentSubject) {
            return response()->json([
                'status' => 'error',
                'message' => 'التسجيل غير موجود'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $studentSubject
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $studentSubject = StudentSubject::find($id);

        if (!$studentSubject) {
            return response()->json([
                'status' => 'error',
                'message' => 'التسجيل غير موجود'
            ], 404);
        }

        $studentSubject->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف التسجيل بنجاح'
        ]);
    }

    /**
     * Get statistics for student subjects
     */
    public function statistics(Request $request)
    {
        $stats = [
            'total_records' => StudentSubject::count(),
            'total_students' => StudentSubject::distinct('student_id')->count(),
            'total_subjects' => StudentSubject::distinct('subject_id')->count(),
            'exam_types' => StudentSubject::selectRaw('exam_type, COUNT(*) as count')
                ->groupBy('exam_type')
                ->get(),
            'average_mark' => StudentSubject::whereNotNull('mark')->avg('mark'),
            'max_mark' => StudentSubject::whereNotNull('mark')->max('mark'),
            'min_mark' => StudentSubject::whereNotNull('mark')->min('mark'),
            'passed_count' => StudentSubject::where('mark', '>=', 50)->count(),
            'failed_count' => StudentSubject::where('mark', '<', 50)->whereNotNull('mark')->count(),
            'records_by_month' => StudentSubject::selectRaw('MONTH(date) as month, YEAR(date) as year, COUNT(*) as count')
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get()
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    /**
     * Search student subjects
     */
    public function search(Request $request)
    {
        $query = StudentSubject::with(['student', 'subject']);

        if ($request->has('q') && !empty($request->q)) {
            $searchTerm = $request->q;

            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('student', function ($studentQuery) use ($searchTerm) {
                    $studentQuery->where(function ($sq) use ($searchTerm) {
                        $sq->where('residential_address', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('city', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('comment', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('gender', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('birth_date', 'LIKE', "%{$searchTerm}%");
                    });
                })
                    ->orWhereHas('subject', function ($subjectQuery) use ($searchTerm) {
                        $subjectQuery->where(function ($sq) use ($searchTerm) {
                            $sq->where('name', 'LIKE', "%{$searchTerm}%")
                                ->orWhere('comment', 'LIKE', "%{$searchTerm}%");
                        });
                    })
                    ->orWhere('note', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('exam_type', 'LIKE', "%{$searchTerm}%");
            });
        }

        // الترتيب
        $sortBy = $request->get('sort_by', 'date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // التصفح
        $perPage = $request->get('per_page', 15);
        $results = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
                'has_more_pages' => $results->hasMorePages(),
            ]
        ]);
    }
    /**
     * Get student's grades for all subjects
     */
    public function getStudentGrades($studentId)
    {
        $student = Student::find($studentId);

        if (!$student) {
            return response()->json([
                'status' => 'error',
                'message' => 'الطالب غير موجود'
            ], 404);
        }

        $grades = StudentSubject::with('subject')
            ->where('student_id', $studentId)
            ->get()
            ->groupBy('exam_type');

        $allMarks = StudentSubject::where('student_id', $studentId)
            ->whereNotNull('mark')
            ->pluck('mark');

        $average = $allMarks->count() > 0 ? $allMarks->avg() : null;

        return response()->json([
            'status' => 'success',
            'data' => [
                'student' => $student,
                'grades' => $grades,
                'average' => $average,
                'total_subjects' => $grades->flatten()->count(),
                'passed_subjects' => $grades->flatten()->filter(function ($item) {
                    return $item->mark >= 50;
                })->count()
            ]
        ]);
    }

    /**
     * Get subject statistics
     */
    public function getSubjectStats($subjectId)
    {
        $subject = Subject::find($subjectId);

        if (!$subject) {
            return response()->json([
                'status' => 'error',
                'message' => 'المادة غير موجودة'
            ], 404);
        }

        $stats = StudentSubject::where('subject_id', $subjectId)
            ->whereNotNull('mark')
            ->selectRaw('
                COUNT(*) as total_students,
                AVG(mark) as average_mark,
                MAX(mark) as max_mark,
                MIN(mark) as min_mark,
                SUM(CASE WHEN mark >= 50 THEN 1 ELSE 0 END) as passed,
                SUM(CASE WHEN mark < 50 THEN 1 ELSE 0 END) as failed
            ')
            ->first();

        $examTypes = StudentSubject::where('subject_id', $subjectId)
            ->whereNotNull('mark')
            ->selectRaw('exam_type, COUNT(*) as count, AVG(mark) as average')
            ->groupBy('exam_type')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'subject' => $subject,
                'statistics' => $stats,
                'exam_types' => $examTypes,
                'pass_rate' => $stats->total_students > 0
                    ? round(($stats->passed / $stats->total_students) * 100, 2)
                    : 0
            ]
        ]);
    }

    /**
     * Export student grades
     */
    public function exportStudentGrades($studentId)
    {
        $student = Student::find($studentId);

        if (!$student) {
            return response()->json([
                'status' => 'error',
                'message' => 'الطالب غير موجود'
            ], 404);
        }

        $grades = StudentSubject::with('subject')
            ->where('student_id', $studentId)
            ->get();

        $export = [
            'student_name' => $student->name,
            'student_id' => $student->id,
            'export_date' => now()->format('Y-m-d H:i:s'),
            'grades' => $grades->map(function ($grade) {
                return [
                    'subject' => $grade->subject->name,
                    'exam_type' => $grade->exam_type,
                    'date' => $grade->date->format('Y-m-d'),
                    'mark' => $grade->mark,
                    'duration' => $grade->duration,
                    'note' => $grade->note,
                    'status' => $grade->mark && $grade->mark >= 50 ? 'ناجح' : 'راسب'
                ];
            }),
            'summary' => [
                'total_subjects' => $grades->count(),
                'total_exams' => $grades->whereNotNull('mark')->count(),
                'average' => $grades->whereNotNull('mark')->avg('mark'),
                'passed' => $grades->filter(function ($g) {
                    return $g->mark && $g->mark >= 50;
                })->count(),
                'failed' => $grades->filter(function ($g) {
                    return $g->mark && $g->mark < 50;
                })->count()
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $export
        ]);
    }

    /**
     * Get records by exam type
     */
    public function getByExamType($examType)
    {
        $records = StudentSubject::with(['student', 'subject'])
            ->where('exam_type', $examType)
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $records
        ]);
    }

    /**
     * Get records by date range
     */
    public function getByDateRange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $records = StudentSubject::with(['student', 'subject'])
            ->whereBetween('date', [$request->from, $request->to])
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $records
        ]);
    }

    /**
     * Get passed students
     */
    public function getPassedStudents(Request $request)
    {
        $query = StudentSubject::with(['student', 'subject'])
            ->where('mark', '>=', 50)
            ->whereNotNull('mark');

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        $results = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $results
        ]);
    }

    /**
     * Get failed students
     */
    public function getFailedStudents(Request $request)
    {
        $query = StudentSubject::with(['student', 'subject'])
            ->where('mark', '<', 50)
            ->whereNotNull('mark');

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        $results = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $results
        ]);
    }

    /**
     * Get subject average
     */
    public function getSubjectAverage($subjectId)
    {
        $subject = Subject::find($subjectId);

        if (!$subject) {
            return response()->json([
                'status' => 'error',
                'message' => 'المادة غير موجودة'
            ], 404);
        }

        $average = StudentSubject::where('subject_id', $subjectId)
            ->whereNotNull('mark')
            ->avg('mark');

        $count = StudentSubject::where('subject_id', $subjectId)
            ->whereNotNull('mark')
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'subject' => $subject->name,
                'average_mark' => round($average, 2),
                'total_students' => $count
            ]
        ]);
    }


    /**
     * Get top students
     */
    public function getTopStudents(Request $request)
    {
        $limit = $request->get('limit', 10);
        $subjectId = $request->get('subject_id');

        $query = StudentSubject::with(['student', 'subject'])
            ->whereNotNull('mark');

        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }

        $topStudents = $query->orderBy('mark', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $topStudents
        ]);
    }

    /**
     * Generate student report
     */
    public function generateReport($studentId)
    {
        $student = Student::find($studentId);

        if (!$student) {
            return response()->json([
                'status' => 'error',
                'message' => 'الطالب غير موجود'
            ], 404);
        }

        $grades = StudentSubject::with('subject')
            ->where('student_id', $studentId)
            ->get();

        $report = [
            'student_info' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'generated_at' => now()->format('Y-m-d H:i:s')
            ],
            'subjects' => $grades->map(function ($grade) {
                return [
                    'subject' => $grade->subject->name,
                    'exam_type' => $grade->exam_type,
                    'date' => $grade->date->format('Y-m-d'),
                    'mark' => $grade->mark,
                    'duration' => $grade->duration,
                    'note' => $grade->note,
                    'status' => $grade->mark && $grade->mark >= 50 ? '✅ ناجح' : '❌ راسب'
                ];
            }),
            'summary' => [
                'total_exams' => $grades->whereNotNull('mark')->count(),
                'overall_average' => $grades->whereNotNull('mark')->avg('mark'),
                'total_passed' => $grades->filter(function ($g) {
                    return $g->mark && $g->mark >= 50;
                })->count(),
                'total_failed' => $grades->filter(function ($g) {
                    return $g->mark && $g->mark < 50;
                })->count(),
                'performance' => $grades->whereNotNull('mark')->count() > 0
                    ? ($grades->filter(function ($g) {
                        return $g->mark && $g->mark >= 50;
                    })->count() / $grades->whereNotNull('mark')->count()) * 100
                    : 0
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $report
        ]);
    }
}
