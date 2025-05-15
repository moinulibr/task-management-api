<?php

namespace Tests\Unit\API\V1;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskAssignmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_assign_task_to_users()
    {
        $task = Task::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Act: assign both users to the task
        $task->assignees()->attach([$user1->id, $user2->id]);

        // Assert
        $this->assertCount(2, $task->assignees);
        $this->assertTrue($task->assignees->contains($user1));
        $this->assertTrue($task->assignees->contains($user2));
    }
}
