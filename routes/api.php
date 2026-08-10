<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\TeacherController;
use App\Http\Controllers\Dashboard\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::group(['prefix' => 'dashboard', 'as' => 'dashboard.', 'middleware' => ['auth:sanctum']], function () {
    Route::get('teachers/statistics', [TeacherController::class, 'statistics'])->name('teachers.statistics');
    Route::get('teachers/search', [TeacherController::class, 'search'])->name('teachers.search');
    Route::resource('teachers', TeacherController::class);
});
