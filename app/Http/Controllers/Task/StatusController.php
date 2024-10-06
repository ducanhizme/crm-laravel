<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatusRequest;
use App\Http\Resources\StatusResource;
use App\Models\Status;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $currentWorkspace = $request->current_workspace;
        $this->authorize('viewAny', $currentWorkspace);
        $statuses = $currentWorkspace->statuses()->with('tasks')->get();
        return $this->respondWithSuccess($statuses, 'Statuses retrieved successfully');
    }

    public function store(StatusRequest $request)
    {
        $this->authorize('create', Status::class);
        $currentWorkspace = $request->current_workspace;
        $status = $currentWorkspace->statuses()->create($request->validated());
        return $this->respondCreated(new StatusResource($status), 'Status created successfully', 201);
    }

    public function show(Request $request, Status $status)
    {
        $currentWorkspace = $request->current_workspace;
        $this->authorize('view', [$status, $currentWorkspace]);
        return $this->respondWithSuccess(new StatusResource($status), 'Status retrieved successfully');
    }

    public function update(StatusRequest $request, Status $status)
    {
        $currentWorkspace = $request->current_workspace;
        $this->authorize('update', [$status, $currentWorkspace]);
        $status->update($request->validated());
        return $this->respondWithSuccess(new StatusResource($status), 'Status updated successfully');
    }

    public function destroy(Request $request,Status $status)
    {
        $currentWorkspace = $request->current_workspace;
        $this->authorize('update', [$status, $currentWorkspace]);
        $status->delete();
        return $this->respondOk('Status deleted successfully');
    }
}
