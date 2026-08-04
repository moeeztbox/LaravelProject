<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Any authenticated user may list projects; nothing in the RBAC spec
     * restricts project visibility, only project modification.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user may view a single project.
     */
    public function view(User $user, Project $project): bool
    {
        return true;
    }

    /**
     * Any authenticated user may create a project. Ownership of the created
     * project (created_by) is what later scopes their update permission.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Admin and Manager may update any project; everyone else only a
     * project they created (created_by), using the existing schema.
     */
    public function update(User $user, Project $project): bool
    {
        if ($user->hasAnyRole(['Admin', 'Manager'])) {
            return true;
        }

        return (int) $project->created_by === (int) $user->id;
    }

    /**
     * Only Admins may delete a project (replaces the Week 3 AdminGuard).
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->hasRole('Admin');
    }
}
