<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestUsers;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestUsers;

    // --- Create ---

    public function test_admin_can_create_project(): void
    {
        $this->actingAsRole('Admin');

        $response = $this->postJson('/api/projects', ['title' => 'Admin Project']);

        $response->assertStatus(201)
            ->assertJsonPath('project.title', 'Admin Project');
        $this->assertDatabaseHas('projects', ['title' => 'Admin Project']);
    }

    public function test_manager_can_create_project(): void
    {
        $this->actingAsRole('Manager');

        $response = $this->postJson('/api/projects', ['title' => 'Manager Project']);

        $response->assertStatus(201);
    }

    public function test_employee_can_create_project(): void
    {
        $this->actingAsRole('Employee');

        $response = $this->postJson('/api/projects', ['title' => 'Employee Project']);

        $response->assertStatus(201);
    }

    public function test_created_by_is_taken_from_authenticated_user_not_request_input(): void
    {
        $user = $this->actingAsRole('Employee');

        $response = $this->postJson('/api/projects', [
            'title' => 'Spoof Attempt',
            'created_by' => 999999,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('projects', [
            'title' => 'Spoof Attempt',
            'created_by' => $user->id,
        ]);
    }

    public function test_creating_project_without_title_fails_validation(): void
    {
        $this->actingAsRole('Admin');

        $response = $this->postJson('/api/projects', ['description' => 'no title']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_unauthenticated_user_cannot_create_project(): void
    {
        $response = $this->postJson('/api/projects', ['title' => 'No Auth']);

        $response->assertStatus(401);
    }

    // --- List ---

    public function test_any_authenticated_user_can_list_projects(): void
    {
        Project::factory()->count(2)->create();
        $this->actingAsRole('Employee');

        $response = $this->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'projects');
    }

    public function test_unauthenticated_user_cannot_list_projects(): void
    {
        $response = $this->getJson('/api/projects');

        $response->assertStatus(401);
    }

    // --- Show ---

    public function test_any_authenticated_user_can_view_a_project(): void
    {
        $project = Project::factory()->create();
        $this->actingAsRole('Employee');

        $response = $this->getJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJsonPath('project.id', $project->id);
    }

    // --- Update (ownership) ---

    public function test_owner_can_update_own_project(): void
    {
        $owner = $this->actingAsRole('Employee');
        $project = Project::factory()->create(['created_by' => $owner->id]);

        $response = $this->putJson("/api/projects/{$project->id}", ['title' => 'Updated by Owner']);

        $response->assertStatus(200)
            ->assertJsonPath('project.title', 'Updated by Owner');
    }

    public function test_non_owner_employee_cannot_update_project(): void
    {
        $owner = $this->makeUserWithRole('Employee');
        $project = Project::factory()->create(['created_by' => $owner->id]);

        $this->actingAsRole('Employee');

        $response = $this->putJson("/api/projects/{$project->id}", ['title' => 'Hijacked']);

        $response->assertStatus(403)
            ->assertJson(['message' => 'This action is unauthorized.']);
        $this->assertDatabaseMissing('projects', ['title' => 'Hijacked']);
    }

    public function test_admin_can_update_any_project(): void
    {
        $owner = $this->makeUserWithRole('Employee');
        $project = Project::factory()->create(['created_by' => $owner->id]);

        $this->actingAsRole('Admin');

        $response = $this->putJson("/api/projects/{$project->id}", ['title' => 'Updated by Admin']);

        $response->assertStatus(200);
    }

    public function test_manager_can_update_any_project(): void
    {
        $owner = $this->makeUserWithRole('Employee');
        $project = Project::factory()->create(['created_by' => $owner->id]);

        $this->actingAsRole('Manager');

        $response = $this->putJson("/api/projects/{$project->id}", ['title' => 'Updated by Manager']);

        $response->assertStatus(200);
    }

    public function test_updating_project_with_invalid_data_fails_validation(): void
    {
        $owner = $this->actingAsRole('Employee');
        $project = Project::factory()->create(['created_by' => $owner->id]);

        $response = $this->putJson("/api/projects/{$project->id}", ['title' => str_repeat('a', 300)]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    // --- Delete ---

    public function test_admin_can_delete_project(): void
    {
        $project = Project::factory()->create();
        $this->actingAsRole('Admin');

        $response = $this->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Project deleted successfully.']);
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_manager_cannot_delete_project(): void
    {
        $project = Project::factory()->create();
        $this->actingAsRole('Manager');

        $response = $this->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(403)
            ->assertJson(['message' => 'This action is unauthorized.']);
    }

    public function test_employee_cannot_delete_project(): void
    {
        $project = Project::factory()->create();
        $this->actingAsRole('Employee');

        $response = $this->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(403);
    }

    public function test_employee_cannot_delete_own_project(): void
    {
        $owner = $this->actingAsRole('Employee');
        $project = Project::factory()->create(['created_by' => $owner->id]);

        $response = $this->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_unauthenticated_user_cannot_delete_project(): void
    {
        $project = Project::factory()->create();

        $response = $this->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(401);
    }
}
