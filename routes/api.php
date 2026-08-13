<?php

use App\Http\Controllers\Dashboard\ClassController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\StudentController;
use App\Http\Controllers\Dashboard\TeacherController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\ParentController;
use App\Http\Controllers\Dashboard\FileController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::group(['prefix' => 'dashboard', 'as' => 'dashboard.', 'middleware' => ['auth:sanctum']], function () {

    // Teacher Routes
    Route::get('teachers/statistics', [TeacherController::class, 'statistics'])->name('teachers.statistics');
    Route::get('teachers/search', [TeacherController::class, 'search'])->name('teachers.search');
    Route::get('teachers/gender/{gender}', [TeacherController::class, 'getTeachersByGender'])->name('teachers.gender');
    Route::get('teachers/phone/{phone}', [TeacherController::class, 'getTeachersByPhone'])->name('teachers.phone');
    Route::resource('teachers', TeacherController::class);

    //parents Routes
    Route::get('parents/statistics', [ParentController::class, 'statistics'])->name('parents.statistics');
    Route::get('parents/search', [ParentController::class, 'search'])->name('parents.search');
    Route::get('parents/{parentId}/children', [ParentController::class, 'getChildren'])->name('parents.children');
    Route::resource('parents', ParentController::class);

    //Auth
    Route::post('/logout', [AuthController::class, 'logout']);    
    Route::post('/logout-all', [AuthController::class, 'logoutAllDevices']);

    //Class Routes
    Route::resource('classes', ClassController::class);


    // Student Routes 
    Route::get('students/parents-list', [StudentController::class, 'getParentsList']);
    Route::get('students/sections-list', [StudentController::class, 'getSectionsList']);
    Route::get('students/classes-list', [StudentController::class, 'getClassesList']);
    Route::get('students', [StudentController::class, 'index']);
    Route::post('students', [StudentController::class, 'store']);
    Route::get('students/search', [StudentController::class, 'search']);
    Route::get('students/statistics', [StudentController::class, 'statistics']);
    Route::get('students/{id}', [StudentController::class, 'show']);
    Route::put('students/{id}', [StudentController::class, 'update']);
    Route::delete('students/{id}', [StudentController::class, 'destroy']);

    // Student Filters
    Route::get('students/class/{classId}', [StudentController::class, 'getStudentsByClass']);
    Route::get('students/section/{sectionId}', [StudentController::class, 'getStudentsBySection']);

     // File Routes 
    Route::get('files', [FileController::class, 'index'])->name('files.index');
    Route::get('files/{id}', [FileController::class, 'show'])->name('files.show');
    Route::get('files/{id}/download', [FileController::class, 'download'])->name('files.download');
    Route::get('files/subject/{subjectId}', [FileController::class, 'getFilesBySubject'])->name('files.by-subject');
    
    // File Routes (Teachers only - with CheckRole middleware)
    /* Route::middleware(['checkRole'])->group(function () {
        Route::post('files', [FileController::class, 'store'])->name('files.store');
        Route::put('files/{id}', [FileController::class, 'update'])->name('files.update');
        Route::delete('files/{id}', [FileController::class, 'destroy'])->name('files.destroy');
    }); */
    Route::post('files', [FileController::class, 'store'])->name('files.store');
        Route::put('files/{id}', [FileController::class, 'update'])->name('files.update');
        Route::delete('files/{id}', [FileController::class, 'destroy'])->name('files.destroy');

});
