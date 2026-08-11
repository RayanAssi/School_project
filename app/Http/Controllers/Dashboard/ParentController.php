<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Parente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ParentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $parents = Parente::with('user')->paginate(15);
        return response()->json([
            'success' => true,
            'data' => $parents
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                // Fields for parents table
                'full_name_father' => 'required|string|max:255',
                'full_name_mother' => 'required|string|max:255',
                'job_father' => 'required|string|max:255',
                'job_mother' => 'required|string|max:255',
                'phone_number_father' => 'required|string|max:20',
                'phone_number_mother' => 'required|string|max:20',
                // Fields for users table
                'user_name' => 'required|string|unique:users,user_name|max:255',
                'full_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            // Create user in users table
            $user = User::create([
                'user_name' => $validated['user_name'],
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'user_type' => 'parent', // Set user type as parent
            ]);

            // Create parent in parents table
            $parent = Parente::create([
                'full_name_father' => $validated['full_name_father'],
                'full_name_mother' => $validated['full_name_mother'],
                'job_father' => $validated['job_father'],
                'job_mother' => $validated['job_mother'],
                'phone_number_father' => $validated['phone_number_father'],
                'phone_number_mother' => $validated['phone_number_mother'],
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Parent created successfully',
                'data' => $parent->load('user')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating parent',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $parent = Parente::with('user')->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $parent
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parent not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $parent = Parente::findOrFail($id);

            $validated = $request->validate([
                // Fields for parents table
                'full_name_father' => 'sometimes|required|string|max:255',
                'full_name_mother' => 'sometimes|required|string|max:255',
                'job_father' => 'sometimes|required|string|max:255',
                'job_mother' => 'sometimes|required|string|max:255',
                'phone_number_father' => 'sometimes|required|string|max:20',
                'phone_number_mother' => 'sometimes|required|string|max:20',
                // Fields for users table
                'user_name' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('users', 'user_name')->ignore($parent->user_id),
                ],
                'full_name' => 'sometimes|required|string|max:255',
                'email' => [
                    'sometimes',
                    'required',
                    'email',
                    Rule::unique('users', 'email')->ignore($parent->user_id),
                ],
                'password' => 'sometimes|nullable|string|min:8|confirmed',
            ]);

            // Update parent data (only fields in parents table)
            $parentData = [];
            $parentFields = [
                'full_name_father', 
                'full_name_mother', 
                'job_father', 
                'job_mother', 
                'phone_number_father', 
                'phone_number_mother'
            ];
            
            foreach ($parentFields as $field) {
                if (isset($validated[$field])) {
                    $parentData[$field] = $validated[$field];
                }
            }
            
            if (!empty($parentData)) {
                $parent->update($parentData);
            }

            // Update user data (fields in users table)
            $userData = [];
            $userFields = ['user_name', 'full_name', 'email', 'device_token'];
            
            foreach ($userFields as $field) {
                if ($request->has($field)) {
                    $userData[$field] = $validated[$field];
                }
            }
            
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($validated['password']);
            }
            
            if (!empty($userData)) {
                $parent->user->update($userData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Parent updated successfully',
                'data' => $parent->fresh()->load('user')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating parent',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $parent = Parente::findOrFail($id);
            
            // Delete user (cascade will delete parent automatically)
            $parent->user->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Parent deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting parent',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get children (students) of a parent.
     */
    public function children($id)
    {
        try {
            $parent = Parente::with('user')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $parent,
                'message' => 'Children functionality needs relationship setup'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching children',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}