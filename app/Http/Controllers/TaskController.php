<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\AttachCollaboratorRequest;
use App\Http\Requests\Task\SyncCollaboratorsRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Http\Resources\UserResource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    /**
     * Update a task. Authorization is enforced by the `can:update,task`
     * route middleware (see TaskPolicy::update), so no manual check here.
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $task->update($request->validated());

        return response()->json([
            'message' => 'Task updated successfully.',
            'task' => new TaskResource($task),
        ]);
    }

    /**
     * List the users collaborating on this task (many-to-many via task_user).
     */
    public function collaborators(Task $task): JsonResponse
    {
        return response()->json([
            'collaborators' => UserResource::collection($task->collaborators),
        ]);
    }

    /**
     * Add one collaborator without disturbing the existing ones.
     * Uses syncWithoutDetaching so re-adding the same user is a safe no-op
     * rather than a duplicate-pivot-row error.
     */
    public function attachCollaborator(AttachCollaboratorRequest $request, Task $task): JsonResponse
    {
        $task->collaborators()->syncWithoutDetaching([$request->validated('user_id')]);

        return response()->json([
            'message' => 'Collaborator added successfully.',
            'collaborators' => UserResource::collection($task->collaborators),
        ]);
    }

    /**
     * Remove one collaborator, leaving all others untouched.
     */
    public function detachCollaborator(Task $task, User $user): JsonResponse
    {
        $task->collaborators()->detach($user->id);

        return response()->json([
            'message' => 'Collaborator removed successfully.',
        ]);
    }

    /**
     * Replace the entire collaborator list with exactly the given set.
     */
    public function syncCollaborators(SyncCollaboratorsRequest $request, Task $task): JsonResponse
    {
        $task->collaborators()->sync($request->validated('user_ids'));

        return response()->json([
            'message' => 'Collaborators synced successfully.',
            'collaborators' => UserResource::collection($task->collaborators),
        ]);
    }
}
