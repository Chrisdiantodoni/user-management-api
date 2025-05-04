<?php

use App\Http\Controllers\API\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::prefix('v1')->group(function () {
    Route::prefix("auth")->group(function () {
        Route::post("/login", [UserController::class, "login"]);
    });
    Route::middleware(["auth:sanctum"])->group(function () {
        Route::get('/users', [UserController::class, 'getListUser']);
        Route::post('/users/{id}/change-password', [UserController::class, 'changePassword']);
        Route::post('/users', [UserController::class, 'register']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::get('/user', [UserController::class, 'profile']);
        Route::put('/users/{id}/edit', [UserController::class, 'update']);
        Route::put('/users/{id}/reset-password', [UserController::class, 'resetPassword']);
        Route::put('/users/{id}/change-status', [UserController::class, 'updateUserStatus']);
        Route::post('/logout', [UserController::class, 'destroy']);
        Route::delete('/users/delete/{id}', [UserController::class, 'deleteUsers']);
    });
});
