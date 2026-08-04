<?php

namespace Tests\Feature\Concerns;

use App\Models\Role;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

trait CreatesTestUsers
{
    /**
     * Create a user with the given role name (Admin / Manager / Employee),
     * creating the role itself if it doesn't already exist in this test.
     */
    protected function makeUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    /**
     * Create a user with the given role and authenticate as them via Sanctum.
     */
    protected function actingAsRole(string $roleName): User
    {
        $user = $this->makeUserWithRole($roleName);

        Sanctum::actingAs($user);

        return $user;
    }
}
