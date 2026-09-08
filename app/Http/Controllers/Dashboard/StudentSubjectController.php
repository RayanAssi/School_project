<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        // جلب جميع المواد مع علاماتها الكاملة
        $subjects = Subject::all()->keyBy('id');

        $studentSubjects = StudentSubject::whereNotNull('mark')->get();

        // حساب النجاح والرسوب بناءً على 40% من العلامة الكاملة
        $passedCount = 0;
        $failedCount = 0;

        foreach ($studentSubjects as $record) {
            $subject = $subjects->get($record->subject_id);
            if ($subject) {
                $passingMark = $subject->full_mark * 0.4; // 40% من العلامة الكاملة
                if ($record->mark >= $passingMark) {
                    $passedCount++;
                } else {
                    $failedCount++;
                }
            }
        }

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
            'passed_count' => $passedCount,
            'failed_count' => $failedCount,
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
        $student = Student::with('user')->find($studentId);

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

        // حساب عدد المواد الناجحة والراسبة بناءً على 40%
        $passedSubjects = 0;
        $failedSubjects = 0;

        foreach ($grades->flatten() as $grade) {
            if ($grade->subject) {
                $passingMark = $grade->subject->full_mark * 0.4;
                if ($grade->mark !== null && $grade->mark >= $passingMark) {
                    $passedSubjects++;
                } elseif ($grade->mark !== null) {
                    $failedSubjects++;
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'full_name' => $student->user->full_name ?? null,
                    'user_name' => $student->user->user_name ?? null,
                    'email' => $student->user->email ?? null,
                    'gender' => $student->gender,
                    'class_id' => $student->class_id,
                    'section_id' => $student->section_id,
                ],
                'grades' => $grades,
                'average' => $average,
                'total_subjects' => $grades->flatten()->count(),
                'passed_subjects' => $passedSubjects,
                'failed_subjects' => $failedSubjects
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

        $passingMark = $subject->full_mark * 0.4; // 40% من العلامة الكاملة

        $stats = StudentSubject::where('subject_id', $subjectId)
            ->whereNotNull('mark')
            ->selectRaw('
                COUNT(*) as total_students,
                AVG(mark) as average_mark,
                MAX(mark) as max_mark,
                MIN(mark) as min_mark
            ')
            ->first();

        // حساب الناجحين والراسبين بناءً على 40%
        $passed = StudentSubject::where('subject_id', $subjectId)
            ->whereNotNull('mark')
            ->where('mark', '>=', $passingMark)
            ->count();

        $failed = StudentSubject::where('subject_id', $subjectId)
            ->whereNotNull('mark')
            ->where('mark', '<', $passingMark)
            ->count();

        $examTypes = StudentSubject::where('subject_id', $subjectId)
            ->whereNotNull('mark')
            ->selectRaw('exam_type, COUNT(*) as count, AVG(mark) as average')
            ->groupBy('exam_type')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'subject' => $subject,
                'passing_mark' => $passingMark,
                'statistics' => $stats,
                'passed' => $passed,
                'failed' => $failed,
                'exam_types' => $examTypes,
                'pass_rate' => $stats->total_students > 0
                    ? round(($passed / $stats->total_students) * 100, 2)
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

        $exportData = [];
        $passedCount = 0;
        $failedCount = 0;

        foreach ($grades as $grade) {
            if ($grade->subject) {
                $passingMark = $grade->subject->full_mark * 0.4;
                $isPassed = $grade->mark !== null && $grade->mark >= $passingMark;

                if ($grade->mark !== null) {
                    if ($isPassed) {
                        $passedCount++;
                    } else {
                        $failedCount++;
                    }
                }

                $exportData[] = [
                    'subject' => $grade->subject->name,
                    'full_mark' => $grade->subject->full_mark,
                    'passing_mark' => $passingMark,
                    'exam_type' => $grade->exam_type,
                    'date' => $grade->date->format('Y-m-d'),
                    'mark' => $grade->mark,
                    'duration' => $grade->duration,
                    'note' => $grade->note,
                    'status' => $grade->mark !== null
                        ? ($isPassed ? 'ناجح' : 'راسب')
                        : 'غير محدد'
                ];
            }
        }

        $export = [
            'student_name' => $student->user->full_name ?? null,
            'student_id' => $student->id,
            'export_date' => now()->format('Y-m-d H:i:s'),
            'grades' => $exportData,
            'summary' => [
                'total_subjects' => $grades->count(),
                'total_exams' => $grades->whereNotNull('mark')->count(),
                'average' => $grades->whereNotNull('mark')->avg('mark'),
                'passed' => $passedCount,
                'failed' => $failedCount
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
     * Get passed students (based on 40% of full mark)
     */
    public function getPassedStudents(Request $request)
    {
        $query = StudentSubject::with(['student', 'subject'])
            ->whereNotNull('mark');

        if ($request->has('subject_id')) {
            $subjectId = $request->subject_id;
            $subject = Subject::find($subjectId);

            if ($subject) {
                $passingMark = $subject->full_mark * 0.4;
                $query->where('subject_id', $subjectId)
                    ->where('mark', '>=', $passingMark);
            }
        } else {
            // إذا لم يتم تحديد مادة، نأخذ جميع المواد ونفلترها
            $results = $query->get();
            $filteredResults = $results->filter(function ($item) {
                if ($item->subject) {
                    $passingMark = $item->subject->full_mark * 0.4;
                    return $item->mark >= $passingMark;
                }
                return false;
            });

            // Paginate manually
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
                $filteredResults->forPage($page, $perPage),
                $filteredResults->count(),
                $perPage,
                $page,
                ['path' => $request->url()]
            );

            return response()->json([
                'status' => 'success',
                'data' => $paginated
            ]);
        }

        $results = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $results
        ]);
    }

    /**
     * Get failed students (based on 40% of full mark)
     */
    public function getFailedStudents(Request $request)
    {
        $query = StudentSubject::with(['student', 'subject'])
            ->whereNotNull('mark');

        if ($request->has('subject_id')) {
            $subjectId = $request->subject_id;
            $subject = Subject::find($subjectId);

            if ($subject) {
                $passingMark = $subject->full_mark * 0.4;
                $query->where('subject_id', $subjectId)
                    ->where('mark', '<', $passingMark);
            }
        } else {
            // إذا لم يتم تحديد مادة، نأخذ جميع المواد ونفلترها
            $results = $query->get();
            $filteredResults = $results->filter(function ($item) {
                if ($item->subject) {
                    $passingMark = $item->subject->full_mark * 0.4;
                    return $item->mark < $passingMark;
                }
                return false;
            });

            // Paginate manually
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
                $filteredResults->forPage($page, $perPage),
                $filteredResults->count(),
                $perPage,
                $page,
                ['path' => $request->url()]
            );

            return response()->json([
                'status' => 'success',
                'data' => $paginated
            ]);
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

        $passingMark = $subject->full_mark * 0.4;
        $passed = StudentSubject::where('subject_id', $subjectId)
            ->whereNotNull('mark')
            ->where('mark', '>=', $passingMark)
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'subject' => $subject->name,
                'full_mark' => $subject->full_mark,
                'passing_mark' => $passingMark,
                'average_mark' => round($average, 2),
                'total_students' => $count,
                'passed_students' => $passed,
                'failed_students' => $count - $passed
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

        // إضافة نسبة النجاح لكل طالب
        $topStudents = $topStudents->map(function ($item) {
            if ($item->subject) {
                $passingMark = $item->subject->full_mark * 0.4;
                $item->passing_mark = $passingMark;
                $item->is_passed = $item->mark >= $passingMark;
                $item->percentage = round(($item->mark / $item->subject->full_mark) * 100, 2);
            }
            return $item;
        });

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

        $subjectsData = [];
        $passedCount = 0;
        $failedCount = 0;

        foreach ($grades as $grade) {
            if ($grade->subject) {
                $passingMark = $grade->subject->full_mark * 0.4;
                $isPassed = $grade->mark !== null && $grade->mark >= $passingMark;

                if ($grade->mark !== null) {
                    if ($isPassed) {
                        $passedCount++;
                    } else {
                        $failedCount++;
                    }
                }

                $subjectsData[] = [
                    'subject' => $grade->subject->name,
                    'full_mark' => $grade->subject->full_mark,
                    'passing_mark' => $passingMark,
                    'exam_type' => $grade->exam_type,
                    'date' => $grade->date->format('Y-m-d'),
                    'mark' => $grade->mark,
                    'duration' => $grade->duration,
                    'note' => $grade->note,
                    'percentage' => $grade->mark !== null
                        ? round(($grade->mark / $grade->subject->full_mark) * 100, 2)
                        : null,
                    'status' => $grade->mark !== null
                        ? ($isPassed ? '✅ ناجح' : '❌ راسب')
                        : '⚠️ غير محدد'
                ];
            }
        }

        $totalExams = $grades->whereNotNull('mark')->count();
        $overallAverage = $totalExams > 0 ? $grades->whereNotNull('mark')->avg('mark') : 0;

        $report = [
            'student_info' => [
                'id' => $student->id,
                'full_name' => $student->user->full_name ?? null,
                'email' => $student->user->email ?? null,
                'generated_at' => now()->format('Y-m-d H:i:s')
            ],
            'subjects' => $subjectsData,
            'summary' => [
                'total_exams' => $totalExams,
                'overall_average' => round($overallAverage, 2),
                'total_passed' => $passedCount,
                'total_failed' => $failedCount,
                'pass_percentage' => $totalExams > 0
                    ? round(($passedCount / $totalExams) * 100, 2)
                    : 0,
                'passing_threshold' => '40% من العلامة الكاملة لكل مادة'
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $report
        ]);
    }


    /**
     * Store multiple student subjects (grades) at once
     * POST /api/student-subjects/bulk
     */
    public function storeBulk(Request $request)
    {
        try {
            // 1. التحقق من وجود المادة
            $subject = Subject::find($request->subject_id);

            if (!$subject) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'المادة غير موجودة'
                ], 404);
            }

            // 2. التحقق من البيانات
            $validator = Validator::make($request->all(), [
                'subject_id' => 'required|exists:subjects,id',
                'exam_type' => ['required', Rule::in(['نصفي', 'نهائي'])],
                'date' => 'required|date',
                'students' => 'required|array|min:1',
                'students.*.student_id' => 'required|exists:students,id',
                'students.*.mark' => [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:' . $subject->full_mark,
                ],
                'students.*.note' => 'nullable|string|max:500',
                'students.*.duration' => 'nullable|date_format:H:i:s',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // 3. تجهيز البيانات للإدخال
            $results = [];
            $errors = [];
            $successCount = 0;
            $failCount = 0;

            DB::beginTransaction();

            foreach ($request->students as $studentData) {
                // التحقق من التكرار
                $exists = StudentSubject::where('student_id', $studentData['student_id'])
                    ->where('subject_id', $request->subject_id)
                    ->where('exam_type', $request->exam_type)
                    ->exists();

                if ($exists) {
                    $errors[] = [
                        'student_id' => $studentData['student_id'],
                        'message' => 'هذا الطالب مسجل بالفعل لهذه المادة ونوع الامتحان'
                    ];
                    $failCount++;
                    continue;
                }

                // إنشاء التسجيل
                $studentSubject = StudentSubject::create([
                    'student_id' => $studentData['student_id'],
                    'subject_id' => $request->subject_id,
                    'exam_type' => $request->exam_type,
                    'date' => $request->date,
                    'mark' => $studentData['mark'] ?? null,
                    'note' => $studentData['note'] ?? null,
                    'duration' => $studentData['duration'] ?? null,
                ]);

                // ✅ إخفاء duration من النتيجة
                $results[] = $studentSubject->load(['student', 'subject'])->makeHidden(['duration']);
                $successCount++;
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "تم إضافة {$successCount} تسجيل بنجاح",
                'data' => [
                    'subject' => [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'full_mark' => $subject->full_mark,
                    ],
                    'exam_type' => $request->exam_type,
                    'date' => $request->date,
                    'success_count' => $successCount,
                    'fail_count' => $failCount,
                    'successful_records' => $results,
                    'failed_records' => $errors,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء إضافة العلامات',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    
    public function storeExam(Request $request)
    {
        try {
            // 1. التحقق من وجود المادة
            $subject = Subject::find($request->subject_id);
            if (!$subject) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'المادة غير موجودة'
                ], 404);
            }

            // 2. التحقق من صحة البيانات
            $validator = Validator::make($request->all(), [
                'subject_id' => 'required|exists:subjects,id',
                'section_id' => 'required|exists:sections,id',
                'exam_type' => ['required', Rule::in(['نصفي', 'نهائي'])],
                'date' => 'required|date',
                'duration' => 'nullable|date_format:H:i:s',
                'note' => 'nullable|string|max:500',
                'mark' => [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:' . $subject->full_mark,
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // 3. جلب جميع طلاب القسم
            $students = Student::where('section_id', $request->section_id)->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'لا يوجد طلاب في هذا القسم'
                ], 404);
            }

            // 4. تجهيز المتغيرات
            $results = [];
            $errors = [];
            $successCount = 0;
            $failCount = 0;

            // 5. بدء المعاملة
            DB::beginTransaction();

            // 6. التكرار على طلاب القسم
            foreach ($students as $student) {
                // التحقق من التكرار
                $exists = StudentSubject::where('student_id', $student->id)
                    ->where('subject_id', $request->subject_id)
                    ->where('exam_type', $request->exam_type)
                    ->exists();

                if ($exists) {
                    $errors[] = [
                        'student_id' => $student->id,
                        'student_name' => $student->name,
                        'message' => 'هذا الطالب مسجل بالفعل لهذه المادة ونوع الامتحان'
                    ];
                    $failCount++;
                    continue;
                }

                // إنشاء التسجيل
                $studentSubject = StudentSubject::create([
                    'student_id' => $student->id,
                    'subject_id' => $request->subject_id,
                    'exam_type' => $request->exam_type,
                    'date' => $request->date,
                    'duration' => $request->duration ?? null,
                    'note' => $request->note ?? null,
                    'mark' => $request->mark ?? null,
                ]);

                // إضافة للنتائج الناجحة
                $results[] = $studentSubject->load(['student', 'subject'])->makeHidden(['duration']);
                $successCount++;
            }

            // 7. إنهاء المعاملة
            DB::commit();

            // 8. إرجاع الاستجابة
            return response()->json([
                'status' => 'success',
                'message' => "تم إضافة الاختبار لـ {$successCount} طالب بنجاح",
                'data' => [
                    'section' => [
                        'id' => $request->section_id,
                        'name' => $students->first()->section->name ?? 'غير معروف',
                    ],
                    'subject' => [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'full_mark' => $subject->full_mark,
                    ],
                    'exam_type' => $request->exam_type,
                    'date' => $request->date,
                    'duration' => $request->duration,
                    'note' => $request->note,
                    'mark' => $request->mark,
                    'total_students' => $students->count(),
                    'success_count' => $successCount,
                    'fail_count' => $failCount,
                    'successful_records' => $results,
                    'failed_records' => $errors,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء إضافة الاختبار',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
/**
 * Get exams by class and section (grade records for a specific class/section)
 * GET /api/student-subjects/by-class-section
 */
public function getByClassSection(Request $request)
{
    $validator = Validator::make($request->all(), [
        'class_id' => 'required|exists:classes,id',
        'section_id' => 'required|exists:sections,id',
        'exam_type' => 'nullable|in:نصفي,نهائي',
        'subject_id' => 'nullable|exists:subjects,id',
        'from_date' => 'nullable|date',
        'to_date' => 'nullable|date|after_or_equal:from_date',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ], 422);
    }

    // 1. جلب جميع طلاب الشعبة المحددة
    $studentIds = Student::where('class_id', $request->class_id)
        ->where('section_id', $request->section_id)
        ->pluck('id');

    if ($studentIds->isEmpty()) {
        return response()->json([
            'status' => 'success',
            'data' => [],
            'message' => 'لا يوجد طلاب في هذه الشعبة'
        ]);
    }

    // 2. جلب الاختبارات (العلامات) لهؤلاء الطلاب
    $query = StudentSubject::with(['subject'])
        ->whereIn('student_id', $studentIds)
        ->whereNotNull('mark'); // العلامات المسجلة فقط

    // فلترة حسب نوع الامتحان
    if ($request->has('exam_type') && $request->exam_type) {
        $query->where('exam_type', $request->exam_type);
    }

    // فلترة حسب المادة
    if ($request->has('subject_id') && $request->subject_id) {
        $query->where('subject_id', $request->subject_id);
    }

    // فلترة حسب التاريخ
    if ($request->has('from_date')) {
        $query->whereDate('date', '>=', $request->from_date);
    }
    if ($request->has('to_date')) {
        $query->whereDate('date', '<=', $request->to_date);
    }

    // ترتيب حسب التاريخ (الأحدث أولاً)
    $query->orderBy('date', 'desc');

    // جلب جميع النتائج (بدون pagination)
    $results = $query->get();

    // تحويل البيانات لإرجاع الحقول المطلوبة فقط
    $transformedData = $results->map(function ($item) {
        return [
            'subject_name' => optional($item->subject)->name ?? 'غير محدد',
            'exam_type' => $item->exam_type,
            'mark' => $item->mark,
            'date' => $item->date ? $item->date->format('Y-m-d') : null,
            'note' => $item->note,
        ];
    });

    return response()->json([
        'status' => 'success',
        'data' => $transformedData,
        'count' => $transformedData->count(),
        'filters' => [
            'class_id' => $request->class_id,
            'section_id' => $request->section_id,
            'exam_type' => $request->exam_type,
            'subject_id' => $request->subject_id,
        ]
    ]);
}

/**
 * Get all exams for a specific subject with type, duration, and date only
 * GET /api/student-subjects/subject/{subjectId}/exams-list
 */
public function getSubjectExamsList($subjectId)
{
    try {
        // 1. التحقق من وجود المادة
        $subject = Subject::find($subjectId);
        if (!$subject) {
            return response()->json([
                'status' => 'error',
                'message' => 'المادة غير موجودة'
            ], 404);
        }

        // 2. جلب جميع الاختبارات لهذه المادة (مميزة حسب نوع الامتحان والتاريخ)
        $exams = StudentSubject::where('subject_id', $subjectId)
            ->whereNotNull('mark')
            ->select('exam_type', 'date', 'duration')
            ->distinct()
            ->get();

        // 3. تجهيز البيانات
        $examList = $exams->map(function ($item) {
            return [
                'exam_type' => $item->exam_type,
                'date' => $item->date ? $item->date->format('Y-m-d') : null,
                'duration' => $item->duration,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'subject' => [
                    'id' => $subject->id,
                    'name' => $subject->name,
                ],
                'total_exams' => $examList->count(),
                'exams' => $examList,
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'حدث خطأ أثناء جلب اختبارات المادة',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
