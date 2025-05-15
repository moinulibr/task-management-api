<?php
namespace Tests\Feature\API\V1;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_create_task()
    {
        $user = User::factory()->create();

        $payload = [
            'title' => 'Test Task',
            'description' => 'This is a test task',
            'status' => 'todo',
            'due_date' => now()->addDays(3)->toDateString(),
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/tasks', $payload);

        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'title' => 'Test Task',
                     'description' => 'This is a test task',
                     'status' => 'todo',
                 ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
        ]);
    }

    #[Test]
    public function user_can_view_tasks()
    {
        $user     = User::factory()->create();
        $other    = User::factory()->create();
        $task1    = Task::factory()->create(['user_id' => $user->id, 'title' => 'My Task']);
        $task2    = Task::factory()->create();
        $task3    = Task::factory()->create(['user_id' => $other->id, 'title' => 'Other Task']);
        $task3->assignees()->attach($user->id);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/tasks');

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'My Task'])
            ->assertJsonFragment(['title' => 'Other Task'])
            ->assertJsonMissing(['title' => $task2->title]);
    }

    #[Test]
    public function user_can_update_task(): void
    {
        $user = User::factory()->create();

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Old Title',
            'status' => TaskStatus::Todo,
            'priority' => TaskPriority::Low,
        ]);

        $payload = [
            'title' => 'Updated Title',
            'status' => TaskStatus::InProgress->value,
            'priority' => TaskPriority::High->value,
        ];

        $response = $this->actingAs($user)->putJson("/api/v1/tasks/{$task->id}", $payload);

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'title' => 'Updated Title',
                    'status' => TaskStatus::InProgress->value,
                    'priority' => TaskPriority::High->value,
                ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
            'status' => TaskStatus::InProgress->value,
            'priority' => TaskPriority::High->value,
        ]);
    }

    #[Test]
    public function user_can_delete_task(): void
    {
        $user = User::factory()->create();

        $task = Task::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);
    }

    #[Test]
    public function user_can_filter_tasks_by_status_priority_and_due_date()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Todo Task',
            'status' => 'todo',
            'priority' => 'low',
            'due_date' => now()->addDays(1)->toDateString(),
        ]);

        Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Done Task',
            'status' => 'done',
            'priority' => 'high',
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        $response = $this->getJson('/api/v1/tasks?status=done&priority=high');

        $response->assertStatus(200)
                ->assertJsonFragment(['title' => 'Done Task'])
                ->assertJsonMissing(['title' => 'Todo Task']);
    }

    #[Test]
    public function user_can_sort_tasks_by_due_date_descending()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task1 = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Earlier Task',
            'due_date' => now()->addDays(1)->toDateString(),
        ]);

        $task2 = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Later Task',
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        $response = $this->getJson('/api/v1/tasks?sort=-due_date');

        $response->assertStatus(200);
        $this->assertEquals('Later Task', $response->json('data')[0]['title']);
    }

}
