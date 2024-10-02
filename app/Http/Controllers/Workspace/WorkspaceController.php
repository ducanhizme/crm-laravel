<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkspaceRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkspaceController extends Controller
{
    use AuthorizesRequests;
    private User $currentUser;

    public function __construct()
    {
        $this->currentUser =\Auth::user();
    }


    public function index()
    {
        $this->authorize('viewAny', Workspace::class);
        $workspaces = $this->currentUser->joinedWorkspace()->get();
        return $this->successResponse(WorkspaceResource::collection($workspaces),"Workspaces retrieved successfully");
    }

    public function store(WorkspaceRequest $request)
    {
        $this->authorize('create', Workspace::class);
        $workspace = $this->currentUser->createWorkspace($request->validated());
        return $this->successResponse(new WorkspaceResource($workspace), 'Workspace created successfully');
    }

    public function show(Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $memberCount = $workspace->users()->count();
        return $this->successResponse([
            'workspace' => new WorkspaceResource($workspace),
            'member_count' => $memberCount,
        ], 'Workspace created successfully');
    }

    public function update(WorkspaceRequest $request, Workspace $workspace)
    {
        $this->authorize('update', $workspace);
        $workspace->update($request->validated());
        return $this->successResponse(new WorkspaceResource($workspace), 'Workspace updated successfully');
    }

    public function destroy(Workspace $workspace)
    {
        $this->authorize('delete', $workspace);
        $workspace->delete();
        return $this->successResponse([], 'Workspace deleted successfully');
    }

    public function removeUser(Request $request){
        $workspace = $request->current_workspace;
            $userId= $request->validate([
                'user_id' => ['required']
            ])['user_id'];
            $this->authorize('removeUser', [$workspace, $userId]);
            $workspace->users()->detach($userId);
        return $this->successResponse(new WorkspaceResource($workspace), 'Remove user workspace  successfully');
    }

    public function leaveWorkspaces (Request $request)
    {
        $workspaceId = $request->validate([
            'workspace_id' => ['required','exists:workspaces,id']
        ])['workspace_id'];
        $status = $this->currentUser->leaveWorkspace($workspaceId) ? 'successfully': 'failed';
        return $this->successResponse([], 'Left workspace '.$status);
    }
}
