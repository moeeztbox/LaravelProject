<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Admin and Manager may update any task; everyone else only their own
     * assigned task (replaces the Week 3 TaskOwnershipGuard).
     */
    public function update(User $user, Task $task): bool
    {
        if ($user->hasRole('Admin') || $user->hasRole('Manager')) {
            return true;
        }

        return $task->assigned_to === $user->id;
    }
}
