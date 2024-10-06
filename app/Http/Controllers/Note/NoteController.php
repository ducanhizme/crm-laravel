<?php

namespace App\Http\Controllers\Note;

use App\Http\Controllers\Controller;
use App\Http\Requests\NoteRequest;
use App\Http\Resources\NoteResource;
use App\Models\Note;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $currentWorkspace = $request->current_workspace;
        $this->authorize('viewAny', [Note::class, $currentWorkspace]);
        $notes = $currentWorkspace->notes;
        return $this->respondWithSuccess(NoteResource::collection($notes),"Notes retrieved successfully");
    }

    public function store(NoteRequest $request)
    {
        $currentWorkspace = $request->current_workspace;
        $this->authorize('viewAny', [Note::class, $currentWorkspace]);
        $note = $currentWorkspace->createNote($request->validated());
        return $this->respondCreated(new NoteResource($note), 'Note created successfully');
    }

    public function show(Request $request,Note $note)
    {
        $currentWorkspace = $request->current_workspace;
        $this->authorize('view', [$note, $currentWorkspace]);
        return $this->respondWithSuccess(new NoteResource($note), 'Note retrieved successfully');
    }

    public function update(NoteRequest $request, Note $note)
    {
        $currentWorkspace = $request->current_workspace;
        $this->authorize('update', [$note, $currentWorkspace]);
        $note->update($request->validated());
        return $this->respondWithSuccess(new NoteResource($note), 'Note updated successfully');
    }

    public function destroy(Request $request,Note $note)
    {
        $currentWorkspace = $request->current_workspace;
        $this->authorize('delete', [$note, $currentWorkspace]);
        $note->delete();
        return $this->respondOk('Note deleted successfully');
    }
}
