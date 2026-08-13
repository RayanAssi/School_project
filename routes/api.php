<?php

use App\Http\Controllers\Dashboard\ClassController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\StudentController;
use App\Http\Controllers\Dashboard\TeacherController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\ParentController;
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

});
