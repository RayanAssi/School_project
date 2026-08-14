<?php

use App\Http\Controllers\Dashboard\AdministratorController;
use App\Http\Controllers\Dashboard\SectionController;
use App\Http\Controllers\Dashboard\UserController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'dashboard', 'as' => 'dashboard.', 'middleware' => ['auth:sanctum']], function () {
    //User
    Route::get('/show_profile', [UserController::class, 'show']);
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users/{id}/reset-username', [UserController::class, 'resetUserName']);
    Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);
    

    //Administrator
    Route::get('/admins', [AdministratorController::class, 'index']);
    Route::post('/admins', [AdministratorController::class, 'store']);
    Route::put('/admins/{id}', [AdministratorController::class, 'update']);
    Route::delete('/admins/{id}', [AdministratorController::class, 'destroy'])->middleware('check.role');
    Route::post('/admins/{id}/reset-username', [AdministratorController::class, 'resetUserName']);
    Route::post('/admins/{id}/reset-password', [AdministratorController::class, 'resetPassword']);

    //Section
    Route::get('/sections', [SectionController::class, 'index']);
    Route::post('/sections', [SectionController::class, 'store']);
    Route::put('/sections/{id}', [SectionController::class, 'update']);
    Route::delete('/sections/{id}', [SectionController::class, 'destroy']);
    Route::get('/sections/statistics', [SectionController::class, 'statistics']);
    Route::get('/sections/{id}', [SectionController::class, 'show']);
});
