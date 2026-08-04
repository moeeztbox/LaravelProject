<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Any authenticated user may attempt to list tasks. This does not by
     * itself limit *which* tasks appear — TaskController::index() filters
     * the query to assigned-only tasks for non-Admin/Manager users, since a
     * Policy boolean can gate the whole action but can't filter a collection.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Admin and Manager may view any task; everyone else only a task
     * assigned to them (Employee: "view only assigned tasks").
     */
    public function view(User $user, Task $task): bool
    {
        return $this->isElevatedOrAssignee($user, $task);
    }

    /**
     * Only Admin and Manager may create tasks. Tasks have no creator/owner
     * column in the existing schema, so there is no ownership basis on
     * which to let an Employee create one, unlike Project (created_by).
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Manager']);
    }

    /**
     * Admin and Manager may update any task; everyone else only their own
     * assigned task (replaces the Week 3 TaskOwnershipGuard). Unchanged.
     */
    public function update(User $user, Task $task): bool
    {
        return $this->isElevatedOrAssignee($user, $task);
    }

    /**
     * Only Admin and Manager may delete tasks. The RBAC spec grants
     * Employees "view" and "update" of their own assigned task only —
     * delete is not among their granted actions.
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->hasAnyRole(['Admin', 'Manager']);
    }

    /**
     * Only Admin and Manager may assign or reassign a task, for the same
     * reason as delete: assignment is not among an Employee's granted
     * actions (view/update their own assigned task only).
     */
    public function assign(User $user, Task $task): bool
    {
        return $user->hasAnyRole(['Admin', 'Manager']);
    }

    /**
     * Shared by view() and update(): Admin/Manager may act on any task;
     * everyone else only on a task assigned to them.
     */
    private function isElevatedOrAssignee(User $user, Task $task): bool
    {
        if ($user->hasAnyRole(['Admin', 'Manager'])) {
            return true;
        }

        return (int) $task->assigned_to === (int) $user->id;
    }
}
