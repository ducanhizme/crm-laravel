<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeopleRequest;
use App\Http\Resources\PeopleResource;
use App\Models\People;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class PeopleController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $currentWorkspace = $request->current_workspace;
        $this->authorize('viewAny', [People::class,$currentWorkspace]);
        $people = $currentWorkspace->people;
        return $this->respondWithSuccess(PeopleResource::collection($people),'People retrieved successfully');
    }

    public function store(PeopleRequest $request)
    {
        $currentWorkspace = $request->current_workspace;
        $this->authorize('create', [People::class,$currentWorkspace]);
        $person = $currentWorkspace->people()->create($request->validated()+['created_by'=>auth()->id()]);
        return $this->respondCreated(new PeopleResource($person), 'People created successfully');
    }

    public function show(Request $request,People $person)
    {
        $currentWorkspace = $request->current_workspace;
        $this->authorize('view', [$person,$currentWorkspace]);
        return $this->respondWithSuccess(new PeopleResource($person),'People retrieved successfully');
    }

    public function update(PeopleRequest $request, People $person)
    {
        $currentWorkspace = $request->current_workspace;
        $this->authorize('update', [$person,$currentWorkspace]);
        $person->update($request->validated());
        return $this->respondWithSuccess(new PeopleResource($person),'People updated successfully');
    }

    public function destroy(Request $request,People $person)
    {
        $currentWorkspace = $request->current_workspace;
        $this->authorize('delete', [$person,$currentWorkspace]);
        $person->delete();
        return $this->respondOk('People deleted successfully');
    }
}
