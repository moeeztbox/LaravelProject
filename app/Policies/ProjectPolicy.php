<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Only Admins may delete a project (replaces the Week 3 AdminGuard).
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->hasRole('Admin');
    }
}
