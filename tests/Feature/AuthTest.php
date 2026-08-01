<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'user', 'token']);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'The provided credentials are incorrect.',
            ]);
    }

    public function test_non_admin_cannot_delete_project(): void
    {
        $role = Role::factory()->create(['name' => 'User']);

        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($user);

        $project = Project::factory()->create();

        $response = $this->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);
    }
}
