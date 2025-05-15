<?php

use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    
    Route::controller(AuthController::class)->prefix('auth')->group(function () {
        Route::post('/register', 'register');
        Route::post('/login', 'login');
    });

    //Protected Routes (Require Token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function(){
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/show', [AuthController::class, 'show']);
        });

        Route::apiResource('tasks', TaskController::class);
        Route::post('tasks/{task}/assign', [TaskController::class, 'taskAssignToUser']);
        Route::post('users/{user}/assign-tasks', [TaskController::class, 'assignMultipleTasksToUser']);
        
        Route::get('users/{user}/assigned-tasks-count', [TaskController::class, 'assignedTasksCount']);
        
        Route::get('users/{user}/assigned-tasks', [TaskController::class, 'assignedTasks']); 

        Route::post('tasks/{id}/restore', [TaskController::class, 'restore']);
        Route::delete('tasks/{id}/force-delete', [TaskController::class, 'forceDelete']);

        Route::get('tasks/trashed', [TaskController::class, 'trashedTasks']);
    });
});
