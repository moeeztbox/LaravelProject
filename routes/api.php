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

    // All Project routes authorized in the controller via $this->authorize() -> ProjectPolicy
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::put('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

    // All Task routes authorized via the 'can' middleware -> TaskPolicy
    Route::get('/tasks', [TaskController::class, 'index'])->middleware('can:viewAny,App\Models\Task');
    Route::post('/tasks', [TaskController::class, 'store'])->middleware('can:create,App\Models\Task');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->middleware('can:view,task');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->middleware('can:update,task');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->middleware('can:delete,task');
    Route::put('/tasks/{task}/assign', [TaskController::class, 'assign'])->middleware('can:assign,task');

    // Collaborator management reuses the 'update' ability -> TaskPolicy::update
    Route::middleware('can:update,task')->group(function () {
        Route::get('/tasks/{task}/collaborators', [TaskController::class, 'collaborators']);
        Route::post('/tasks/{task}/collaborators', [TaskController::class, 'attachCollaborator']);
        Route::put('/tasks/{task}/collaborators', [TaskController::class, 'syncCollaborators']);
        Route::delete('/tasks/{task}/collaborators/{user}', [TaskController::class, 'detachCollaborator']);
    });
});
