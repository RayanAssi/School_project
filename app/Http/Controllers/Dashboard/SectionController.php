<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    // get all sections with their classes and teachers
    public function index(Request $request)
    {
        $query = Section::with(['class', 'teachers'])
            ->withCount(['teachers', 'students']);

        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $sections = $query->paginate(10);
        if ($sections->isEmpty()) {
            return response()->json([
                'message' => 'No sections have been created yet',
                'data' => $sections,
            ], 200);
        }
        return response()->json([
            'message' => 'Sections retrieved successfully',
            'data' => $sections,
        ], 200);
    }
    // create a new section with its class and teachers
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sections')->where(function ($query) use ($request) {
                    return $query->where('class_id', $request->class_id);
                }),
            ],
            'comment' => 'nullable|string|max:500',
            'class_id' => 'required|exists:classes,id',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:teachers,id',
        ]);

        $section = Section::create([
            'name' => $validated['name'],
            'comment' => $validated['comment'] ?? null,
            'class_id' => $validated['class_id'],
        ]);

        if (isset($validated['teacher_ids']) && !empty($validated['teacher_ids'])) {
            $section->teachers()->attach($validated['teacher_ids']);
        }
        $section->load(['class', 'teachers'])->loadCount(['teachers', 'students']);

        return response()->json([
            'message' => 'Section created successfully',
            'data' => $section,
        ], 201);
    }
    //edit section
    public function update(Request $request, $id)
    {
        $section = Section::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sections')->where(function ($query) use ($request) {
                    return $query->where('class_id', $request->class_id);
                })->ignore($section->id),
            ],
            'comment' => 'nullable|string|max:500',
            'class_id' => 'required|exists:classes,id',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:teachers,id',
        ]);

        $section->update([
            'name' => $validated['name'],
            'comment' => $validated['comment'] ?? null,
            'class_id' => $validated['class_id'],
        ]);

        if ($request->has('teacher_ids')) {
            if (empty($validated['teacher_ids'])) {
                $section->teachers()->detach();
            } else {
                $section->teachers()->sync($validated['teacher_ids']);
            }
        }
        $section->load(['class', 'teachers'])->loadCount(['teachers', 'students']);

        return response()->json([
            'message' => 'Section updated successfully',
            'data' => $section,
        ], 200);
    }
    //show section with its class and teachers
    public function show($id)
    {
        $section = Section::with([
            'class',
            'teachers',
            'students'
        ])->withCount([
            'teachers',
            'students'
        ])->findOrFail($id);

        return response()->json([
            'message' => 'Section retrieved successfully',
            'data' => $section,
        ], 200);
    }

    //delete section
    public function destroy($id)
    {
        $section = Section::findOrFail($id);
        $section->delete();

        return response()->json([
            'message' => 'Section deleted successfully',
            'data' => null,
        ], 200);
    }
    //statistics of sections, teachers and students
    public function statistics()
    {
        $sections = Section::withCount(['teachers', 'students'])->get();

        $statistics = [
            'total_sections' => $sections->count(),
            'total_teachers' => $sections->sum('teachers_count'),
            'total_students' => $sections->sum('students_count'),
            'max_teachers_in_section' => $sections->max('teachers_count') ?? 0,
            'max_students_in_section' => $sections->max('students_count') ?? 0,
            'min_teachers_in_section' => $sections->min('teachers_count') ?? 0,
            'min_students_in_section' => $sections->min('students_count') ?? 0,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Statistics retrieved successfully',
            'data' => $statistics,
        ], 200);
    }
}
