<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\AssignTaskRequest;
use App\Http\Requests\Task\AttachCollaboratorRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\SyncCollaboratorsRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Http\Resources\UserResource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * List tasks. Authorization is enforced by the `can:viewAny,...` route
     * middleware (see TaskPolicy::viewAny), but that only gates the action
     * as a whole — it can't filter which tasks appear. Admin/Manager see
     * every task; everyone else sees only tasks assigned to them (Employee:
     * "view only assigned tasks").
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $tasks = $user->hasAnyRole(['Admin', 'Manager'])
            ? Task::all()
            : Task::where('assigned_to', $user->id)->get();

        return response()->json([
            'tasks' => TaskResource::collection($tasks),
        ]);
    }

    /**
     * Create a task. Authorization is enforced by the `can:create,...`
     * route middleware (see TaskPolicy::create).
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = Task::create($request->validated());

        return response()->json([
            'message' => 'Task created successfully.',
            'task' => new TaskResource($task),
        ], 201);
    }

    /**
     * Show a single task. Authorization is enforced by the `can:view,task`
     * route middleware (see TaskPolicy::view).
     */
    public function show(Task $task): JsonResponse
    {
        return response()->json([
            'task' => new TaskResource($task),
        ]);
    }

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
     * Delete a task. Authorization is enforced by the `can:delete,task`
     * route middleware (see TaskPolicy::delete).
     */
    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully.',
        ]);
    }

    /**
     * Assign or reassign the task to a user. Authorization is enforced by
     * the `can:assign,task` route middleware (see TaskPolicy::assign).
     */
    public function assign(AssignTaskRequest $request, Task $task): JsonResponse
    {
        $task->update([
            'assigned_to' => $request->validated('assigned_to'),
        ]);

        return response()->json([
            'message' => 'Task assigned successfully.',
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
