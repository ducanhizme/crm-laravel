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
        return NoteResource::collection($notes);
    }

    public function store(NoteRequest $request)
    {
        $currentWorkspace = $request->current_workspace;
        $this->authorize('viewAny', [Note::class, $currentWorkspace]);
        $note = $currentWorkspace->createNote($request->validated());
        return new NoteResource($note);
    }

    public function show(Request $request,Note $note)
    {
        $currentWorkspace = $request->current_workspace;
        $this->authorize('view', [$note, $currentWorkspace]);
        return new NoteResource($note);
    }

    public function update(NoteRequest $request, Note $note)
    {
        $currentWorkspace = $request->current_workspace;
        $this->authorize('update', [$note, $currentWorkspace]);
        $note->update($request->validated());
        return new NoteResource($note);
    }

    public function destroy(Request $request,Note $note)
    {
        $currentWorkspace = $request->current_workspace;
        $this->authorize('delete', [$note, $currentWorkspace]);
        $note->delete();
        return response()->json();
    }
}
