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
     * Get list of files with optional subject filter
     * GET /api/files?subject_id=1
     */
    public function index()
{
    try {
        $files = File::with(['subject:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Add download URL to each file
        $files->transform(function ($file) {
            $file->download_url = url('/api/dashboard/files/' . $file->id . '/download');
            return $file;
        });

        return response()->json([
            'success' => true,
            'data' => $files,
            'total' => $files->count(),
            'message' => 'Files retrieved successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve files',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Upload a new file (teachers only)
     * POST /api/files
     */
    public function store(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'subject_id' => 'required|exists:subjects,id',
            'file_path' => 'required|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip,rar|max:10240', // Max 10MB
        ], [
            'name.required' => 'File name is required',
            'name.string' => 'File name must be a string',
            'name.max' => 'File name cannot exceed 255 characters',
            'subject_id.required' => 'Subject is required',
            'subject_id.exists' => 'Subject does not exist',
            'file_path.required' => 'File is required',
            'file_path.file' => 'Must be a valid file',
            'file_path.mimes' => 'File type not supported. Supported types: pdf, doc, docx, ppt, pptx, xls, xlsx, txt, zip, rar',
            'file_path.max' => 'File size must not exceed 10MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Upload file
            $uploadedFile = $request->file('file_path');
            $extension = $uploadedFile->getClientOriginalExtension();
            
            // Generate unique filename
            $fileName = time() . '_' . uniqid() . '.' . $extension;
            
            // Store file in 'uploads/files' directory
            $path = $uploadedFile->storeAs('uploads/files', $fileName, 'public');

            // Create database record
            $file = File::create([
                'name' => $request->name,
                'subject_id' => $request->subject_id,
                'file_path' => $path,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'file' => $file->load('subject'),
                    'download_url' => url('/api/dashboard/files/' . $file->id . '/download')
                ],
                'message' => 'File uploaded successfully'
            ], 201);

        } catch (\Exception $e) {
            // Delete file if error occurs
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific file details
     * GET /api/files/{id}
     */
    public function show($id)
    {
        try {
            $file = File::with(['subject:id,name'])
                ->find($id);

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            $file->download_url = url('/api/dashboard/files/' . $file->id . '/download');

            return response()->json([
                'success' => true,
                'data' => $file,
                'message' => 'File details retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve file details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download file (students and teachers)
     * GET /api/files/{id}/download
     */
    public function download($id)
    {
        try {
            $file = File::find($id);

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            // Check if file exists in storage
            if (!Storage::disk('public')->exists($file->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found on server'
                ], 404);
            }

            // Download file
            $filePath = Storage::disk('public')->path($file->file_path);
            
            // Get file extension from path
            $extension = pathinfo($file->file_path, PATHINFO_EXTENSION);
            
            return response()->download(
                $filePath, 
                $file->name . '.' . $extension,
                [
                    'Content-Type' => $this->getMimeType($extension),
                ]
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update file details (teachers only)
     * PUT /api/files/{id}
     */
public function update(Request $request, $id)
{
    $file = File::find($id);

    if (!$file) {
        return response()->json([
            'success' => false,
            'message' => 'File not found'
        ], 404);
    }

    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|required|string|max:255',
        'subject_id' => 'sometimes|required|exists:subjects,id',
    ], [
        'name.required' => 'File name is required',
        'name.string' => 'File name must be a string',
        'name.max' => 'File name cannot exceed 255 characters',
        'subject_id.required' => 'Subject is required',
        'subject_id.exists' => 'Subject does not exist',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        // استخدم fill بدلاً من update
        $file->fill($request->only(['name', 'subject_id']));
        $file->save();

        return response()->json([
            'success' => true,
            'data' => $file->load('subject'),
            'message' => 'File details updated successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update file',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Delete a file (teachers only)
     * DELETE /api/files/{id}
     */
    public function destroy($id)
    {
        $file = File::find($id);

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }

        try {
            // Delete file from storage
            if (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }

            // Delete record from database
            $file->delete();

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get files by subject
     * GET /api/files/subject/{subjectId}
     */
    public function getFilesBySubject($subjectId)
    {
        try {
            $subject = Subject::find($subjectId);

            if (!$subject) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject not found'
                ], 404);
            }

            $files = File::where('subject_id', $subjectId)
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            // Add download URL to each file
            $files->getCollection()->transform(function ($file) {
                $file->download_url = url('/api/dashboard/files/' . $file->id . '/download');
                return $file;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'subject' => $subject->only(['id', 'name']),
                    'files' => $files
                ],
                'message' => 'Subject files retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve subject files',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get MIME type based on file extension
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