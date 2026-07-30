<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Authorized in the controller via $this->authorize('delete', $project) -> ProjectPolicy
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

    // Authorized here via the 'can' middleware -> TaskPolicy::update
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->middleware('can:update,task');
});
