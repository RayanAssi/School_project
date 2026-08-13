<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Classes;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Subject::with(['class', 'teachers', 'files']);

            if ($request->has('name') && $request->name) {
                $query->where('name', 'LIKE', "%{$request->name}%");
            }

            if ($request->has('class_id') && $request->class_id) {
                $query->where('class_id', $request->class_id);
            }

            if ($request->has('full_mark') && $request->full_mark) {
                $query->where('full_mark', $request->full_mark);
            }

            $subjects = $query->get();

            $formattedSubjects = $subjects->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'comment' => $subject->comment,
                    'full_mark' => $subject->full_mark,
                    'class' => [
                        'id' => $subject->class->id ?? null,
                        'name' => $subject->class->name ?? null,
                    ],
                    'teachers' => $subject->teachers->map(function ($teacher) {
                        return [
                            'id' => $teacher->id,
                            'full_name' => $teacher->user->full_name ?? null,
                            'phone_number' => $teacher->phone_number ?? null,
                        ];
                    }),
                    'files_count' => $subject->files->count(),
                    'created_at' => $subject->created_at,
                    'updated_at' => $subject->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedSubjects,
                'total' => $formattedSubjects->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات المواد',
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
                'name' => 'required|string|max:255|unique:subjects,name',
                'comment' => 'nullable|string',
                'full_mark' => 'required|numeric|min:0|max:100',
                'class_id' => 'required|exists:classes,id',
                'teacher_ids' => 'nullable|array',
                'teacher_ids.*' => 'exists:teachers,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // create the subject
            $subject = Subject::create([
                'name' => $request->name,
                'comment' => $request->comment,
                'full_mark' => $request->full_mark,
                'class_id' => $request->class_id,
            ]);

            // Attach teachers if provided
            if ($request->has('teacher_ids') && !empty($request->teacher_ids)) {
                $subject->teachers()->attach($request->teacher_ids);
            }

            DB::commit();

            // Load the class and teachers relationships for the response
            $subject->load(['class', 'teachers']);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء المادة بنجاح',
                'data' => [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'comment' => $subject->comment,
                    'full_mark' => $subject->full_mark,
                    'class' => [
                        'id' => $subject->class->id ?? null,
                        'name' => $subject->class->name ?? null,
                    ],
                    'teachers' => $subject->teachers->map(function ($teacher) {
                        return [
                            'id' => $teacher->id,
                            'full_name' => $teacher->user->full_name ?? null,
                        ];
                    }),
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء المادة',
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
            $subject = Subject::with(['class', 'teachers', 'files', 'students'])->find($id);

            if (!$subject) {
                return response()->json([
                    'success' => false,
                    'message' => 'المادة غير موجودة'
                ], 404);
            }

            $subjectData = [
                'id' => $subject->id,
                'name' => $subject->name,
                'comment' => $subject->comment,
                'full_mark' => $subject->full_mark,
                'class' => [
                    'id' => $subject->class->id ?? null,
                    'name' => $subject->class->name ?? null,
                ],
                'teachers' => $subject->teachers->map(function ($teacher) {
                    return [
                        'id' => $teacher->id,
                        'full_name' => $teacher->user->full_name ?? null,
                        'phone_number' => $teacher->phone_number ?? null,
                    ];
                }),
                'students' => $subject->students->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'full_name' => $student->user->full_name ?? null,
                        'mark' => $student->pivot->mark ?? null,
                        'exam_type' => $student->pivot->exam_type ?? null,
                        'date' => $student->pivot->date ?? null,
                        'note' => $student->pivot->note ?? null,
                        'duration' => $student->pivot->duration ?? null,
                    ];
                }),
                'files' => $subject->files->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'name' => $file->name ?? null,
                        'path' => $file->path ?? null,
                    ];
                }),
                'created_at' => $subject->created_at,
                'updated_at' => $subject->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $subjectData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات المادة',
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
            $subject = Subject::find($id);

            if (!$subject) {
                return response()->json([
                    'success' => false,
                    'message' => 'المادة غير موجودة'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255|unique:subjects,name,' . $id,
                'comment' => 'nullable|string',
                'full_mark' => 'sometimes|numeric|min:0|max:100',
                'class_id' => 'sometimes|exists:classes,id',
                'teacher_ids' => 'nullable|array',
                'teacher_ids.*' => 'exists:teachers,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $subject->update($request->only([
                'name',
                'comment',
                'full_mark',
                'class_id'
            ]));

            // Sync teachers if provided
            if ($request->has('teacher_ids')) {
                $subject->teachers()->sync($request->teacher_ids);
            }

            DB::commit();

            $updatedSubject = Subject::with(['class', 'teachers'])->find($id);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات المادة بنجاح',
                'data' => $updatedSubject
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث بيانات المادة',
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
            $subject = Subject::find($id);

            if (!$subject) {
                return response()->json([
                    'success' => false,
                    'message' => 'المادة غير موجودة'
                ], 404);
            }

            DB::beginTransaction();

            // detach all relationships before deleting the subject  
            $subject->teachers()->detach();
            $subject->students()->detach();

            // delete associated files if any
            if ($subject->files) {
                foreach ($subject->files as $file) {
                    // delete the actual file from storage
                    if (file_exists(public_path($file->path))) {
                        unlink(public_path($file->path));
                    }
                    $file->delete();
                }
            }

            // delete the subject
            $subject->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المادة بنجاح'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المادة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get subjects by class
     */
    public function getSubjectsByClass($classId)
    {
        try {
            $subjects = Subject::with(['class', 'teachers'])
                ->where('class_id', $classId)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $subjects,
                'total' => $subjects->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المواد حسب الصف',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search subjects
     */
    public function search(Request $request)
    {
        try {
            $query = Subject::with(['class', 'teachers']);

            if ($request->has('q') && $request->q) {
                $searchTerm = $request->q;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('comment', 'LIKE', "%{$searchTerm}%")
                        ->orWhereHas('class', function ($c) use ($searchTerm) {
                            $c->where('name', 'LIKE', "%{$searchTerm}%");
                        });
                });
            }

            $subjects = $query->get();

            return response()->json([
                'success' => true,
                'data' => $subjects,
                'total' => $subjects->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء البحث عن المواد',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign teacher to subject
     */
    public function assignTeacher(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'subject_id' => 'required|exists:subjects,id',
                'teacher_id' => 'required|exists:teachers,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $subject = Subject::with(['class'])->find($request->subject_id);
            $teacher = Teacher::with(['user', 'subjects'])->find($request->teacher_id);

            if (!$subject || !$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'المادة أو المدرس غير موجود'
                ], 404);
            }

            // assign the teacher to the subject if not already assigned
            $isNewAssignment = false;
            if (!$subject->teachers()->where('teacher_id', $request->teacher_id)->exists()) {
                $subject->teachers()->attach($request->teacher_id);
                $isNewAssignment = true;
                $message = 'تم تعيين المدرس للمادة بنجاح';
            } else {
                $message = 'المدرس معين بالفعل لهذه المادة';
            }

            DB::commit();

            // Fetch the updated list of subjects for the teacher
            $teacherSubjects = $teacher->subjects()->with(['class'])->get();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'subject' => [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'full_mark' => $subject->full_mark,
                        'class' => [
                            'id' => $subject->class->id ?? null,
                            'name' => $subject->class->name ?? null,
                        ],
                    ],
                    'teacher' => [
                        'id' => $teacher->id,
                        'full_name' => $teacher->user->full_name ?? null,
                        'email' => $teacher->user->email ?? null,
                        'phone_number' => $teacher->phone_number ?? null,
                    ],
                    'is_new_assignment' => $isNewAssignment,
                    'teacher_all_subjects' => $teacherSubjects->map(function ($subj) {
                        return [
                            'id' => $subj->id,
                            'name' => $subj->name,
                            'full_mark' => $subj->full_mark,
                            'class' => [
                                'id' => $subj->class->id ?? null,
                                'name' => $subj->class->name ?? null,
                            ],
                            'created_at' => $subj->created_at,
                        ];
                    }),
                    'total_subjects_for_teacher' => $teacherSubjects->count()
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تعيين المدرس للمادة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove teacher from subject
     */
    public function removeTeacher(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'subject_id' => 'required|exists:subjects,id',
                'teacher_id' => 'required|exists:teachers,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $subject = Subject::with(['class', 'teachers.user'])->find($request->subject_id);
            $teacher = Teacher::with(['user', 'subjects'])->find($request->teacher_id);

            if (!$subject || !$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'المادة أو المدرس غير موجود'
                ], 404);
            }

            // Check if the teacher is assigned to the subject
            $isAssigned = $subject->teachers()->where('teacher_id', $request->teacher_id)->exists();

            if (!$isAssigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا المدرس غير معين لهذه المادة'
                ], 404);
            }

            // Save teacher and subject data before deletion
            $teacherData = [
                'id' => $teacher->id,
                'full_name' => $teacher->user->full_name ?? null,
                'email' => $teacher->user->email ?? null,
                'phone_number' => $teacher->phone_number ?? null,
            ];

            $subjectData = [
                'id' => $subject->id,
                'name' => $subject->name,
                'full_mark' => $subject->full_mark,
                'class' => [
                    'id' => $subject->class->id ?? null,
                    'name' => $subject->class->name ?? null,
                ],
            ];

            // detach the teacher from the subject
            $subject->teachers()->detach($request->teacher_id);

            DB::commit();

            // Fetch the updated list of teachers for the subject
            $remainingTeachers = $subject->teachers()->with('user')->get();
            $teacherRemainingSubjects = $teacher->subjects()->with(['class'])->get();

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء تعيين المدرس من المادة بنجاح',
                'data' => [
                    'removed' => [
                        'teacher' => $teacherData,
                        'subject' => $subjectData,
                    ],
                    'remaining' => [
                        'subject' => [
                            'id' => $subject->id,
                            'name' => $subject->name,
                            'class' => [
                                'id' => $subject->class->id ?? null,
                                'name' => $subject->class->name ?? null,
                            ],
                            'teachers_count' => $remainingTeachers->count(),
                            'teachers' => $remainingTeachers->map(function ($teacher) {
                                return [
                                    'id' => $teacher->id,
                                    'full_name' => $teacher->user->full_name ?? null,
                                    'email' => $teacher->user->email ?? null,
                                    'phone_number' => $teacher->phone_number ?? null,
                                ];
                            }),
                        ],
                        'teacher' => [
                            'id' => $teacher->id,
                            'full_name' => $teacher->user->full_name ?? null,
                            'email' => $teacher->user->email ?? null,
                            'phone_number' => $teacher->phone_number ?? null,
                            'remaining_subjects_count' => $teacherRemainingSubjects->count(),
                            'remaining_subjects' => $teacherRemainingSubjects->map(function ($subject) {
                                return [
                                    'id' => $subject->id,
                                    'name' => $subject->name,
                                    'full_mark' => $subject->full_mark,
                                    'class' => [
                                        'id' => $subject->class->id ?? null,
                                        'name' => $subject->class->name ?? null,
                                    ],
                                ];
                            }),
                        ]
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء تعيين المدرس',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get teachers of a subject
     */
    public function getSubjectTeachers($subjectId)
    {
        try {
            $subject = Subject::with('teachers')->find($subjectId);

            if (!$subject) {
                return response()->json([
                    'success' => false,
                    'message' => 'المادة غير موجودة'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'subject' => [
                        'id' => $subject->id,
                        'name' => $subject->name,
                    ],
                    'teachers' => $subject->teachers->map(function ($teacher) {
                        return [
                            'id' => $teacher->id,
                            'full_name' => $teacher->user->full_name ?? null,
                            'phone_number' => $teacher->phone_number ?? null,
                        ];
                    }),
                    'total' => $subject->teachers->count()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب مدرسي المادة',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
