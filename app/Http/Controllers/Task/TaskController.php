<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use App\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    use AuthorizesRequests;
    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Task::class);
        $workspace = $request->current_workspace;
        $tasks = $workspace->tasks()->with(['status', 'assignee'])->get();
        return $this->successResponse(TaskResource::collection($tasks),'Tasks retrieved successfully');
    }

    public function store(TaskRequest $request)
    {
       $task=  $this->user->createTask(array_merge($request->validated(), ['workspace_id' => $request->current_workspace->id]));
        return $this->successResponse(new TaskResource($task),'Task created successfully',201);
    }

    public function show(Request $request,Task $task)
    {
        $workspace = $request->current_workspace;
        $this->authorize('view', [$task,$workspace]);
        return $this->successResponse(new TaskResource($task),'Task retrieved successfully');
    }

    public function update(TaskRequest $request, Task $task)
    {
        $workspace = $request->current_workspace;
        $this->authorize('update', [$task,$workspace]);
        $task->update($request->validated());
        return $this->successResponse(new TaskResource($task),'Task updated successfully');
    }

    public function destroy(Request $request,Task $task)
    {
        $workspace = $request->current_workspace;
        $this->authorize('delete', [$task,$workspace]);
        $task->delete();
        return $this->successResponse([],'Task deleted successfully');
    }
}
