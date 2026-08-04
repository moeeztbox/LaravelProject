<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestUsers;
use Tests\TestCase;

class TaskAssignmentTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestUsers;

    public function test_admin_can_assign_task(): void
    {
        $project = Project::factory()->create();
        $task = Task::create(['project_id' => $project->id, 'title' => 'Task']);
        $newAssignee = $this->makeUserWithRole('Employee');

        $this->actingAsRole('Admin');

        $response = $this->putJson("/api/tasks/{$task->id}/assign", [
            'assigned_to' => $newAssignee->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('task.assigned_to', $newAssignee->id);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'assigned_to' => $newAssignee->id]);
    }

    public function test_manager_can_assign_task(): void
    {
        $project = Project::factory()->create();
        $task = Task::create(['project_id' => $project->id, 'title' => 'Task']);
        $newAssignee = $this->makeUserWithRole('Employee');

        $this->actingAsRole('Manager');

        $response = $this->putJson("/api/tasks/{$task->id}/assign", [
            'assigned_to' => $newAssignee->id,
        ]);

        $response->assertStatus(200);
    }

    public function test_employee_cannot_assign_own_task_to_someone_else(): void
    {
        $project = Project::factory()->create();
        $me = $this->actingAsRole('Employee');
        $task = Task::create(['project_id' => $project->id, 'assigned_to' => $me->id, 'title' => 'Mine']);
        $someoneElse = $this->makeUserWithRole('Employee');

        $response = $this->putJson("/api/tasks/{$task->id}/assign", [
            'assigned_to' => $someoneElse->id,
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'This action is unauthorized.']);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'assigned_to' => $me->id]);
    }

    public function test_employee_cannot_assign_another_employees_task(): void
    {
        $project = Project::factory()->create();
        $owner = $this->makeUserWithRole('Employee');
        $task = Task::create(['project_id' => $project->id, 'assigned_to' => $owner->id, 'title' => 'Not Mine']);

        $me = $this->actingAsRole('Employee');

        $response = $this->putJson("/api/tasks/{$task->id}/assign", [
            'assigned_to' => $me->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_assigning_task_to_nonexistent_user_fails_validation(): void
    {
        $project = Project::factory()->create();
        $task = Task::create(['project_id' => $project->id, 'title' => 'Task']);

        $this->actingAsRole('Admin');

        $response = $this->putJson("/api/tasks/{$task->id}/assign", [
            'assigned_to' => 999999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['assigned_to']);
    }

    public function test_assigning_task_without_assigned_to_fails_validation(): void
    {
        $project = Project::factory()->create();
        $task = Task::create(['project_id' => $project->id, 'title' => 'Task']);

        $this->actingAsRole('Admin');

        $response = $this->putJson("/api/tasks/{$task->id}/assign", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['assigned_to']);
    }

    public function test_unauthenticated_user_cannot_assign_task(): void
    {
        $project = Project::factory()->create();
        $task = Task::create(['project_id' => $project->id, 'title' => 'Task']);
        $newAssignee = $this->makeUserWithRole('Employee');

        $response = $this->putJson("/api/tasks/{$task->id}/assign", [
            'assigned_to' => $newAssignee->id,
        ]);

        $response->assertStatus(401);
    }
}
