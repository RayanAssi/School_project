<?php

use App\Http\Controllers\Dashboard\AdministratorController;
use App\Http\Controllers\Dashboard\UserController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'dashboard', 'as' => 'dashboard.', 'middleware' => ['auth:sanctum']], function () {
    //User
    Route::get('/show_profile', [UserController::class, 'show']);

    //Administrator
    Route::get('/admins', [AdministratorController::class, 'index']);
    Route::post('/admins', [AdministratorController::class, 'store']);
    Route::put('/admins/{id}', [AdministratorController::class, 'update']);
    Route::delete('/admins/{id}', [AdministratorController::class, 'destroy'])->middleware('check.role');
    Route::post('/admins/{id}/reset-username', [AdministratorController::class, 'resetUserName']);
    Route::post('/admins/{id}/reset-password', [AdministratorController::class, 'resetPassword']);
});
