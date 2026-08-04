<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestUsers;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestUsers;

    // --- Create ---

    public function test_admin_can_create_task(): void
    {
        $project = Project::factory()->create();
        $this->actingAsRole('Admin');

        $response = $this->postJson('/api/tasks', [
            'project_id' => $project->id,
            'title' => 'Admin Task',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('task.title', 'Admin Task');
        $this->assertDatabaseHas('tasks', ['title' => 'Admin Task']);
    }

    public function test_manager_can_create_task(): void
    {
        $project = Project::factory()->create();
        $this->actingAsRole('Manager');

        $response = $this->postJson('/api/tasks', [
            'project_id' => $project->id,
            'title' => 'Manager Task',
        ]);

        $response->assertStatus(201);
    }

    public function test_employee_cannot_create_task(): void
    {
        $project = Project::factory()->create();
        $this->actingAsRole('Employee');

        $response = $this->postJson('/api/tasks', [
            'project_id' => $project->id,
            'title' => 'Employee Task',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'This action is unauthorized.']);
        $this->assertDatabaseMissing('tasks', ['title' => 'Employee Task']);
    }

    public function test_creating_task_without_title_fails_validation(): void
    {
        $project = Project::factory()->create();
        $this->actingAsRole('Admin');

        $response = $this->postJson('/api/tasks', ['project_id' => $project->id]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_creating_task_with_invalid_project_id_fails_validation(): void
    {
        $this->actingAsRole('Admin');

        $response = $this->postJson('/api/tasks', [
            'project_id' => 999999,
            'title' => 'Orphan Task',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['project_id']);
    }

    public function test_unauthenticated_user_cannot_create_task(): void
    {
        $project = Project::factory()->create();

        $response = $this->postJson('/api/tasks', [
            'project_id' => $project->id,
            'title' => 'No Auth',
        ]);

        $response->assertStatus(401);
    }

    // --- List (role-based content filtering) ---

    public function test_admin_sees_all_tasks_in_list(): void
    {
        $project = Project::factory()->create();
        $employeeA = $this->makeUserWithRole('Employee');
        $employeeB = $this->makeUserWithRole('Employee');
        Task::create(['project_id' => $project->id, 'assigned_to' => $employeeA->id, 'title' => 'Task A']);
        Task::create(['project_id' => $project->id, 'assigned_to' => $employeeB->id, 'title' => 'Task B']);

        $this->actingAsRole('Admin');

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'tasks');
    }

    public function test_manager_sees_all_tasks_in_list(): void
    {
        $project = Project::factory()->create();
        $employeeA = $this->makeUserWithRole('Employee');
        $employeeB = $this->makeUserWithRole('Employee');
        Task::create(['project_id' => $project->id, 'assigned_to' => $employeeA->id, 'title' => 'Task A']);
        Task::create(['project_id' => $project->id, 'assigned_to' => $employeeB->id, 'title' => 'Task B']);

        $this->actingAsRole('Manager');

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'tasks');
    }

    public function test_employee_sees_only_own_assigned_tasks_in_list(): void
    {
        $project = Project::factory()->create();
        $me = $this->actingAsRole('Employee');
        $someoneElse = $this->makeUserWithRole('Employee');

        $myTask = Task::create(['project_id' => $project->id, 'assigned_to' => $me->id, 'title' => 'My Task']);
        Task::create(['project_id' => $project->id, 'assigned_to' => $someoneElse->id, 'title' => 'Not My Task']);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'tasks')
            ->assertJsonPath('tasks.0.id', $myTask->id);
    }

    public function test_unauthenticated_user_cannot_list_tasks(): void
    {
        $response = $this->getJson('/api/tasks');

        $response->assertStatus(401);
    }

    // --- Show (ownership) ---

    public function test_employee_can_view_own_assigned_task(): void
    {
        $project = Project::factory()->create();
        $me = $this->actingAsRole('Employee');
        $task = Task::create(['project_id' => $project->id, 'assigned_to' => $me->id, 'title' => 'Mine']);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonPath('task.id', $task->id);
    }

    public function test_employee_cannot_view_another_employees_task(): void
    {
        $project = Project::factory()->create();
        $owner = $this->makeUserWithRole('Employee');
        $task = Task::create(['project_id' => $project->id, 'assigned_to' => $owner->id, 'title' => 'Not Mine']);

        $this->actingAsRole('Employee');

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(403)
            ->assertJson(['message' => 'This action is unauthorized.']);
    }

    public function test_admin_can_view_any_task(): void
    {
        $project = Project::factory()->create();
        $owner = $this->makeUserWithRole('Employee');
        $task = Task::create(['project_id' => $project->id, 'assigned_to' => $owner->id, 'title' => 'Any Task']);

        $this->actingAsRole('Admin');

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
    }

    public function test_manager_can_view_any_task(): void
    {
        $project = Project::factory()->create();
        $owner = $this->makeUserWithRole('Employee');
        $task = Task::create(['project_id' => $project->id, 'assigned_to' => $owner->id, 'title' => 'Any Task']);

        $this->actingAsRole('Manager');

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
    }

    // --- Update (ownership) ---

    public function test_employee_can_update_own_assigned_task(): void
    {
        $project = Project::factory()->create();
        $me = $this->actingAsRole('Employee');
        $task = Task::create(['project_id' => $project->id, 'assigned_to' => $me->id, 'title' => 'Mine']);

        $response = $this->putJson("/api/tasks/{$task->id}", ['status' => 'in_progress']);

        $response->assertStatus(200)
            ->assertJsonPath('task.status', 'in_progress');
    }

    public function test_employee_cannot_update_another_employees_task(): void
    {
        $project = Project::factory()->create();
        $owner = $this->makeUserWithRole('Employee');
        $task = Task::create(['project_id' => $project->id, 'assigned_to' => $owner->id, 'title' => 'Not Mine']);

        $this->actingAsRole('Employee');

        $response = $this->putJson("/api/tasks/{$task->id}", ['status' => 'blocked']);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id, 'status' => 'blocked']);
    }

    public function test_admin_can_update_any_task(): void
    {
        $project = Project::factory()->create();
        $owner = $this->makeUserWithRole('Employee');
        $task = Task::create(['project_id' => $project->id, 'assigned_to' => $owner->id, 'title' => 'Task']);

        $this->actingAsRole('Admin');

        $response = $this->putJson("/api/tasks/{$task->id}", ['status' => 'done']);

        $response->assertStatus(200);
    }

    public function test_manager_can_update_any_task(): void
    {
        $project = Project::factory()->create();
        $owner = $this->makeUserWithRole('Employee');
        $task = Task::create(['project_id' => $project->id, 'assigned_to' => $owner->id, 'title' => 'Task']);

        $this->actingAsRole('Manager');

        $response = $this->putJson("/api/tasks/{$task->id}", ['status' => 'done']);

        $response->assertStatus(200);
    }

    public function test_updating_task_with_invalid_data_fails_validation(): void
    {
        $project = Project::factory()->create();
        $me = $this->actingAsRole('Employee');
        $task = Task::create(['project_id' => $project->id, 'assigned_to' => $me->id, 'title' => 'Mine']);

        $response = $this->putJson("/api/tasks/{$task->id}", ['deadline' => 'not-a-date']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['deadline']);
    }

    // --- Delete ---

    public function test_admin_can_delete_task(): void
    {
        $project = Project::factory()->create();
        $task = Task::create(['project_id' => $project->id, 'title' => 'Task']);
        $this->actingAsRole('Admin');

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Task deleted successfully.']);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_manager_can_delete_task(): void
    {
        $project = Project::factory()->create();
        $task = Task::create(['project_id' => $project->id, 'title' => 'Task']);
        $this->actingAsRole('Manager');

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
    }

    public function test_employee_cannot_delete_own_assigned_task(): void
    {
        $project = Project::factory()->create();
        $me = $this->actingAsRole('Employee');
        $task = Task::create(['project_id' => $project->id, 'assigned_to' => $me->id, 'title' => 'Mine']);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_employee_cannot_delete_another_employees_task(): void
    {
        $project = Project::factory()->create();
        $owner = $this->makeUserWithRole('Employee');
        $task = Task::create(['project_id' => $project->id, 'assigned_to' => $owner->id, 'title' => 'Not Mine']);

        $this->actingAsRole('Employee');

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_delete_task(): void
    {
        $project = Project::factory()->create();
        $task = Task::create(['project_id' => $project->id, 'title' => 'Task']);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(401);
    }
}
