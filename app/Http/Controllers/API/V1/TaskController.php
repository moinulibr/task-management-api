<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Task;
use App\Models\User;
use App\Traits\HttpResponses;
use App\Traits\HandlesExceptions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\ApiResponseResource;
use App\Http\Resources\PaginatedTaskResource;

class TaskController extends Controller
{
    use HttpResponses, HandlesExceptions;
    
    public function index(Request $request)
    {
        $query = Task::query();

        // Only authenticated user's assigned or created tasks
        $query->where(function ($q) {
            $q->where('user_id', auth()->id())
                ->orWhereHas('assignees', function ($q2) {
                    $q2->where('user_id', auth()->id());
                });
        });

        // Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('due_date')) {
            $query->whereDate('due_date', $request->due_date);
        }

        // Search (title or description)
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        // Sorting
        if ($request->filled('sort')) {
            $sort = $request->sort;
            $direction = Str::startsWith($sort, '-') ? 'desc' : 'asc';
            $column = ltrim($sort, '-');

            if (in_array($column, ['due_date', 'created_at'])) {
                $query->orderBy($column, $direction);
            }
        } else {
            $query->latest();
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $tasks = $query->paginate($perPage);

        return new PaginatedTaskResource($tasks);
    }

    public function store(StoreTaskRequest $request)
    {
        $task = auth()->user()->tasks()->create($request->validated());

        return (new ApiResponseResource(
            new TaskResource($task),
            'Task has been created successfully.',
            Response::HTTP_CREATED
        ))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $id)
    {     
        return $this->handleWithTryCatch(function () use ($id) {
            $task = Task::findOrFail($id);
            
            return (new ApiResponseResource(
                new TaskResource($task->load('assignees')),
                'Task has been fetched successfully.',
                Response::HTTP_OK
            ))->response()->setStatusCode(Response::HTTP_OK);
        });
    }

    public function update(UpdateTaskRequest $request, string $id)
    {
        return $this->handleWithTryCatch(function () use ($request,$id) {
            $task = Task::findOrFail($id);
            $task->update($request->validated());

            return (new ApiResponseResource(
                new TaskResource($task),
                'Task has been updated successfully.',
                Response::HTTP_OK
            ))->response()->setStatusCode(Response::HTTP_OK);
        });
    }

    public function destroy(string $id)
    {
        return $this->handleWithTryCatch(function () use ($id) {
            $task = Task::findOrFail($id);
            $task->delete();
            return (new ApiResponseResource(
                null,
                'Task has been deleted successfully.',
                Response::HTTP_NO_CONTENT
            ))->response()->setStatusCode(Response::HTTP_NO_CONTENT);
        });
    }

    public function taskAssignToUser(Request $request, string $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);
        return $this->handleWithTryCatch(function () use ($id,$request) {
            $task = Task::findOrFail($id);
            $task->assignees()->syncWithoutDetaching([$request->user_id]);
            return (new ApiResponseResource(
                null,
                'Task has been assigned successfully.',
                Response::HTTP_OK
            ))->response()->setStatusCode(Response::HTTP_OK);
        });
    }

    public function assignMultipleTasksToUser(Request $request, string $id)
    {
        $validated = $request->validate([
            'task_ids'   => ['required', 'array'],
            'task_ids.*' => ['exists:tasks,id'],
        ]);

        return $this->handleWithTryCatch(function () use ($id,$validated) {
            $user = User::findOrFail($id);
            $user->assignedTasks()->syncWithoutDetaching($validated['task_ids']);

            return (new ApiResponseResource(
                null,
                'Tasks has been assigned successfully.',
                Response::HTTP_OK
            ))->response()->setStatusCode(Response::HTTP_OK);
        });
    }

    public function assignedTasksCount(string $id)
    {
        return $this->handleWithTryCatch(function () use ($id) {
            $user = User::findOrFail($id);
            $count = $user->assignedTasks()->count();

            return (new ApiResponseResource(
                [
                    'user_id' => $user->id,
                    'assigned_tasks_count' => $count
                ],
                'Tasks has been fetched successfully.',
                Response::HTTP_OK
            ))->response()->setStatusCode(Response::HTTP_OK);
        });
    }

    public function assignedTasks(Request $request, string $id)
    {
        return $this->handleWithTryCatch(function () use ($id,$request) {
            $user = User::findOrFail($id);
            $query = $user->assignedTasks()->with('assignees', 'user');

            // Optional: Filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('priority')) {
                $query->where('priority', $request->priority);
            }

            if ($request->filled('due_date')) {
                $query->whereDate('due_date', $request->due_date);
            }

            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', "%{$request->search}%")
                        ->orWhere('description', 'like', "%{$request->search}%");
                });
            }

            if ($request->filled('sort')) {
                $sort = $request->sort;
                $direction = Str::startsWith($sort, '-') ? 'desc' : 'asc';
                $column = ltrim($sort, '-');

                if (in_array($column, ['due_date', 'created_at'])) {
                    $query->orderBy($column, $direction);
                }
            } else {
                $query->latest();
            }

            $perPage = $request->get('per_page', 10);
            $tasks = $query->paginate($perPage);

            return new PaginatedTaskResource($tasks);
        });
    }

    public function restore(string $id)
    {
        return $this->handleWithTryCatch(function () use ($id) {
            $task = Task::onlyTrashed()->findOrFail($id);
            $task->restore();

            return (new ApiResponseResource(
                new TaskResource($task),
                'Task has been restored successfully.',
                Response::HTTP_OK
            ))->response()->setStatusCode(Response::HTTP_OK);
        });
    }

    public function forceDelete(string $id)
    {
        return $this->handleWithTryCatch(function () use ($id) {
            $task = Task::onlyTrashed()->findOrFail($id);
            $task->forceDelete();

            return (new ApiResponseResource(
                null,
                'Task has been permanently deleted.',
                Response::HTTP_OK
            ))->response()->setStatusCode(Response::HTTP_OK);
        });
    }

    public function trashedTasks(Request $request)
    {
        $query = Task::onlyTrashed()
            ->where('user_id', auth()->id());

        $tasks = $query->paginate($request->get('per_page', 10));

        return new PaginatedTaskResource($tasks);
    }
}
