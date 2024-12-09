<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GCMController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::post('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
Route::post('login', [AuthController::class, 'login']);
Route::post('signup', [AuthController::class, 'signup']);
Route::post('/verify-otp', [AuthController::class, 'verifyLoginOtp']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/password-reset', [AuthController::class, 'resetPassword']);
///protected routes with sanctum
Route::middleware('auth:sanctum')->group(function () {
    //for logout
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/check-auth', [AuthController::class, 'checkAuth'])->name('check-auth');
    //users resource routes
    Route::prefix('users')->name('users.')->group(function () {
        Route::post('/store', [UserController::class, 'store'])->name('store');
        Route::post('/update', [UserController::class, 'update'])->name('update');
        Route::post('/delete', [UserController::class, 'destroy'])->name('delete');
        Route::post('/showAll', [UserController::class, 'index'])->name('showAll');
        Route::post('/show', [UserController::class, 'show'])->name('show');
        Route::post('/users-with-role', [UserController::class, 'userRoles'])->name('users-with-role');
    });


    Route::prefix('permissions')->group(function () {
        Route::post('/showAll', [PermissionController::class, 'index'])->name('permissions.showAll');
        Route::post('/show', [PermissionController::class, 'show'])->name('permissions.show'); // for viewing a single permission
        Route::post('/create', [PermissionController::class, 'store'])->name('permissions.store');
        Route::post('/delete', [PermissionController::class, 'destroy'])->name('permissions.destroy');
    });
    // Roles route
    Route::prefix('roles')->group(function () {
        Route::post('/showAll', [RoleController::class, 'index'])->name('roles.showAll'); // Retrieve all roles
        Route::post('/show', [RoleController::class, 'show'])->name('roles.show'); // Retrieve a specific role
        Route::post('/create', [RoleController::class, 'store'])->name('roles.store'); // Create a new role
        Route::post('/update', [RoleController::class, 'update'])->name('roles.update'); // Update an existing role
        Route::post('/delete', [RoleController::class, 'destroy'])->name('roles.destroy'); // Delete a role
    });

    Route::prefix('project')->group(function () {
        Route::post('/show-all', [ProjectController::class, 'index'])->name('project.show-all');
        Route::post('/show', [ProjectController::class, 'show'])->name('project.show');
        Route::post('/create', [ProjectController::class, 'store'])->name('project.store');
        Route::post('/update', [ProjectController::class, 'update'])->name('project.update');
        Route::post('/delete', [ProjectController::class, 'destroy'])->name('project.destroy');
    });

    Route::prefix('task')->group(function () {
        Route::post('/show-all', [TaskController::class, 'index'])->name('task.show-all');
        Route::post('/show', [TaskController::class, 'show'])->name('task.show');
        Route::post('/create', [TaskController::class, 'store'])->name('task.store');
        Route::post('/update', [TaskController::class, 'update'])->name('task.update');
        Route::post('/delete', [TaskController::class, 'destroy'])->name('task.destroy');
    });


});