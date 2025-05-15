<?php

namespace Tests\Feature\API\V1;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskAssignmentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_assign_task_to_other_user()
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $creator->id]);

        Sanctum::actingAs($creator);

        $response = $this->postJson("/api/v1/tasks/{$task->id}/assign", [
            'user_id' => $assignee->id,
        ]);

        $response->assertStatus(200)
                ->assertJsonFragment(['message' => 'Task has been assigned successfully.']);

        $this->assertDatabaseHas('task_user', [
            'task_id' => $task->id,
            'user_id' => $assignee->id,
        ]);
    }

    #[Test]
    public function task_assignment_requires_valid_user_id()
    {
        $creator = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $creator->id]);

        Sanctum::actingAs($creator);

        $response = $this->postJson("/api/v1/tasks/{$task->id}/assign", [
            'user_id' => null,
        ]);

        $response->assertStatus(422)
        ->assertJsonValidationErrors(['user_id']);
    }
}
