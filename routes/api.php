<?php

use App\Http\Controllers\Dashboard\ClassController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\StudentController;
use App\Http\Controllers\Dashboard\TeacherController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\ParentController;

use App\Http\Controllers\Dashboard\FileController;
use App\Http\Controllers\Dashboard\NotificationController;
use App\Http\Controllers\Dashboard\StudentSubjectController;
use App\Http\Controllers\Dashboard\SubjectController;
use App\Http\Controllers\Dashboard\UserNotificationController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/save-fcm-token', [AuthController::class, 'saveFCMToken'])
        ->middleware('auth:sanctum');
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
    Route::post('/parents/{id}/reset-password', [ParentController::class, 'resetPassword']);

    //Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    

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

    // File Routes (Teachers only - with CheckTeacher middleware)
    Route::middleware(['checkTeacher'])->group(function () {
        Route::post('files', [FileController::class, 'store'])->name('files.store');
        Route::put('files/{id}', [FileController::class, 'update'])->name('files.update');
        Route::delete('files/{id}', [FileController::class, 'destroy'])->name('files.destroy');
    });


    // Subject Routes
    Route::get('subjects/search', [SubjectController::class, 'search'])->name('subjects.search');
    Route::get('subjects/class/{classId}', [SubjectController::class, 'getSubjectsByClass'])->name('subjects.by.class');
    Route::get('subjects/mark-range', [SubjectController::class, 'getSubjectsByMarkRange'])->name('subjects.mark.range');

    // Teacher assignment routes
    Route::post('subjects/assign-teacher', [SubjectController::class, 'assignTeacher'])->name('subjects.assign.teacher');
    Route::post('subjects/remove-teacher', [SubjectController::class, 'removeTeacher'])->name('subjects.remove.teacher');
    Route::get('subjects/{subjectId}/teachers', [SubjectController::class, 'getSubjectTeachers'])->name('subjects.teachers');

    Route::resource('subjects', SubjectController::class);


    // Student Subject Routes
    Route::get('student-subjects/statistics', [StudentSubjectController::class, 'statistics'])->name('student-subjects.statistics');
    Route::get('student-subjects/search', [StudentSubjectController::class, 'search'])->name('student-subjects.search');
    Route::get('student-subjects/student/{studentId}/grades', [StudentSubjectController::class, 'getStudentGrades'])->name('student-subjects.student-grades');
    Route::get('student-subjects/subject/{subjectId}/stats', [StudentSubjectController::class, 'getSubjectStats'])->name('student-subjects.subject-stats');
    Route::get('student-subjects/student/{studentId}/export', [StudentSubjectController::class, 'exportStudentGrades'])->name('student-subjects.export');
    Route::get('student-subjects/exam-type/{examType}', [StudentSubjectController::class, 'getByExamType'])->name('student-subjects.exam-type');
    Route::get('student-subjects/date-range', [StudentSubjectController::class, 'getByDateRange'])->name('student-subjects.date-range');
    Route::get('student-subjects/passed', [StudentSubjectController::class, 'getPassedStudents'])->name('student-subjects.passed');
    Route::get('student-subjects/failed', [StudentSubjectController::class, 'getFailedStudents'])->name('student-subjects.failed');
    Route::get('student-subjects/average/{subjectId}', [StudentSubjectController::class, 'getSubjectAverage'])->name('student-subjects.average');
    Route::get('student-subjects/top-students', [StudentSubjectController::class, 'getTopStudents'])->name('student-subjects.top-students');
    Route::get('student-subjects/student/{studentId}/report', [StudentSubjectController::class, 'generateReport'])->name('student-subjects.report');

    // Resource Routes (must be at the end)
    Route::resource('student-subjects', StudentSubjectController::class);
});


Route::middleware('auth:sanctum')->group(function () {

    // Public notification paths (for administrators)
    Route::prefix('notifications')->group(function () {
        Route::get('/stats', [NotificationController::class, 'stats']);
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/{id}', [NotificationController::class, 'show']);
        Route::post('/', [NotificationController::class, 'store']);
        Route::put('/{id}', [NotificationController::class, 'update']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // Paths specific to the login user
    Route::prefix('my')->group(function () {
        Route::get('/notifications', [NotificationController::class, 'myNotifications']);
        Route::get('/notifications/unread', [NotificationController::class, 'myUnreadNotifications']);
        Route::put('/notifications/{notificationId}/read', [NotificationController::class, 'markMyNotificationAsRead']);
        Route::put('/notifications/read-all', [NotificationController::class, 'markAllMyNotificationsAsRead']);
    });
});
