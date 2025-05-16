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
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Class TaskController
 *
 * Handles all task-related endpoints:
 * - CRUD operations
 * - Assigning tasks to users
 * - Filtering, sorting, pagination
 * - Soft delete, restore, force delete
 * - Task statistics
 *
 * @package App\Http\Controllers\API\V1
 */
class TaskController extends Controller
{
    use HttpResponses, HandlesExceptions;
    
    /**
     * Display a listing of tasks for the authenticated user.
     * Supports filters, search, sort, pagination.
     */
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

    /**
     * Store a newly created task for the authenticated user.
     */
    public function store(StoreTaskRequest $request)
    {
        try{
            $task = auth()->user()->tasks()->create($request->validated());

            return $this->success('Task has been created successfully', Response::HTTP_CREATED,new TaskResource($task) );
        }
        catch (\Exception $e) {
            return $this->error(null, 'Something went wrong!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display a single task by ID.
     */
    public function show(string $id)
    {     
        try{
            $task = Task::findOrFail($id);

            return $this->success('Task has been fetched successfully. successfully', Response::HTTP_OK,new TaskResource($task->load('assignees')) );
        }
        catch (ModelNotFoundException $e) {
            return $this->error(null, 'Resource not found!', Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->error(null, 'Something went wrong!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update a task by ID.
     */
    public function update(UpdateTaskRequest $request, string $id)
    {
        try{
            $task = Task::findOrFail($id);
            $task->update($request->validated());
            
            return $this->success('Task has been updated successfully. successfully', Response::HTTP_OK,new TaskResource($task));
        }
        catch (ModelNotFoundException $e) {
            return $this->error(null, 'Resource not found!', Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->error(null, 'Something went wrong!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Soft delete a task by ID.
     */
    public function destroy(string $id)
    {
        try{
            $task = Task::findOrFail($id);
            $task->delete();
            
            return $this->success('Task has been deleted successfully.', Response::HTTP_NO_CONTENT,null);
        }
        catch (ModelNotFoundException $e) {
            return $this->error(null, 'Resource not found!', Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->error(null, 'Something went wrong!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assign a task to a user (sync without detaching).
     */
    public function taskAssignToUser(Request $request, string $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);
        try{
            $task = Task::findOrFail($id);
            $task->assignees()->syncWithoutDetaching([$request->user_id]);

            return $this->success('Task has been assigned successfully.', Response::HTTP_OK,new TaskResource($task));
        }
        catch (ModelNotFoundException $e) {
            return $this->error(null, 'Resource not found!', Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->error(null, 'Something went wrong!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Assign multiple tasks to a user (bulk).
     */
    public function assignMultipleTasksToUser(Request $request, string $id)
    {
        $validated = $request->validate([
            'task_ids'   => ['required', 'array'],
            'task_ids.*' => ['exists:tasks,id'],
        ]);

        try{
            $user = User::findOrFail($id);
            $user->assignedTasks()->syncWithoutDetaching($validated['task_ids']);

            return $this->success('Tasks has been assigned successfully.', Response::HTTP_OK,null);
        }
        catch (ModelNotFoundException $e) {
            return $this->error(null, 'Resource not found!', Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->error(null, 'Something went wrong!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Return assigned task count for a specific user.
     */
    public function assignedTasksCount(string $id)
    {
        try{
            $user = User::findOrFail($id);
            $count = $user->assignedTasks()->count();

            return $this->success('Tasks has been fetched successfully.', Response::HTTP_OK,new TaskResource([
                'user_id' => $user->id,
                'assigned_tasks_count' => $count
            ]));
        }
        catch (ModelNotFoundException $e) {
            return $this->error(null, 'Resource not found!', Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->error(null, 'Something went wrong!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Return all assigned tasks to a specific user with filters & pagination.
     */
    public function assignedTasks(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);

            $query = $user->assignedTasks()->with('assignees', 'user')

                // Filter by status
                ->when($request->filled('status'), function ($q) use ($request) {
                    $q->where('status', $request->status);
                })

                // Filter by priority
                ->when($request->filled('priority'), function ($q) use ($request) {
                    $q->where('priority', $request->priority);
                })

                // Filter by due_date
                ->when($request->filled('due_date'), function ($q) use ($request) {
                    $q->whereDate('due_date', $request->due_date);
                })

                // Search title or description
                ->when($request->filled('search'), function ($q) use ($request) {
                    $q->where(function ($query) use ($request) {
                        $query->where('title', 'like', "%{$request->search}%")
                            ->orWhere('description', 'like', "%{$request->search}%");
                    });
                })

                // Sort by column (with optional direction)
                ->when($request->filled('sort'), function ($q) use ($request) {
                    $sort = $request->sort;
                    $direction = Str::startsWith($sort, '-') ? 'desc' : 'asc';
                    $column = ltrim($sort, '-');

                    if (in_array($column, ['due_date', 'created_at'])) {
                        $q->orderBy($column, $direction);
                    }
                }, function ($q) {
                    $q->latest(); // Default sort
                });

            $perPage = $request->get('per_page', 10);
            $tasks = $query->paginate($perPage);

            return new PaginatedTaskResource($tasks);

        } catch (ModelNotFoundException $e) {
            return $this->error(null, 'Resource not found!', Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->error(null, 'Something went wrong!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Restore a soft-deleted task.
     */
    public function restore(string $id)
    {
        try{
            $task = Task::onlyTrashed()->findOrFail($id);
            $task->restore();

            return $this->success('Task has been restored successfully.', Response::HTTP_OK,new TaskResource($task));
        }
        catch (ModelNotFoundException $e) {
            return $this->error(null, 'Resource not found!', Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->error(null, 'Something went wrong!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Permanently delete a soft-deleted task.
     */
    public function forceDelete(string $id)
    {
        try{
            $task = Task::onlyTrashed()->findOrFail($id);
            $task->forceDelete();

            return $this->success('Task has been permanently deleted.', Response::HTTP_OK,null);
        }
        catch (ModelNotFoundException $e) {
            return $this->error(null, 'Resource not found!', Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->error(null, 'Something went wrong!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * List all soft-deleted tasks of the authenticated user.
     */
    public function trashedTasks(Request $request)
    {
        try{
            $query = Task::onlyTrashed()
            ->where('user_id', auth()->id());

            $tasks = $query->paginate($request->get('per_page', 10));

            return new PaginatedTaskResource($tasks);
        }
        catch (\Exception $e) {
            return $this->error(null, 'Something went wrong!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
