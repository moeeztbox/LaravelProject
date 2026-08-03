<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/profile/upload', [ProfileController::class, 'upload']);

    // Authorized in the controller via $this->authorize('delete', $project) -> ProjectPolicy
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

    // All authorized via the 'can' middleware -> TaskPolicy::update
    Route::middleware('can:update,task')->group(function () {
        Route::put('/tasks/{task}', [TaskController::class, 'update']);
        Route::get('/tasks/{task}/collaborators', [TaskController::class, 'collaborators']);
        Route::post('/tasks/{task}/collaborators', [TaskController::class, 'attachCollaborator']);
        Route::put('/tasks/{task}/collaborators', [TaskController::class, 'syncCollaborators']);
        Route::delete('/tasks/{task}/collaborators/{user}', [TaskController::class, 'detachCollaborator']);
    });
});
