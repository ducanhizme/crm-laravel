<?php

namespace App\Http\Controllers;

use App\Http\Requests\NoteRequest;
use App\Http\Resources\NoteResource;
use App\Models\Note;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;

class NoteController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Note::class);

        return NoteResource::collection(Note::all());
    }

    public function store(NoteRequest $request)
    {
        $this->authorize('create', Note::class);
        $currentWorkspace = $request->currentWorkspace;
        $note = $currentWorkspace->createNote($request);
        return new NoteResource($note);
    }

    public function show(Note $note)
    {
        $this->authorize('view', $note);

        return new NoteResource($note);
    }

    public function update(NoteRequest $request, Note $note)
    {
        $this->authorize('update', $note);

        $note->update($request->validated());

        return new NoteResource($note);
    }

    public function destroy(Note $note)
    {
        $this->authorize('delete', $note);

        $note->delete();

        return response()->json();
    }
}
