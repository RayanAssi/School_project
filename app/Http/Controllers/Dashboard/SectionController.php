<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
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
        // ربط الأساتذة بالشعبة (في الجدول الوسيط)
        if (isset($validated['teacher_ids']) && !empty($validated['teacher_ids'])) {
            $section->teachers()->attach($validated['teacher_ids']);
        }

        return response()->json([
            'message' => 'Section created successfully',
            'data' => $section->load(['class', 'teachers']),
        ], 201);
    }
}
