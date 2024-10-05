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


    public function __construct()
    {
        $currentWorkspace = request()->currentWorkspace;
        $this->authorizeResource(Note::class, ['note', $currentWorkspace]);
    }

    public function index()
    {
        return NoteResource::collection(Note::all());
    }

    public function store(NoteRequest $request)
    {
        $currentWorkspace = $request->currentWorkspace;
        $note = $currentWorkspace->createNote($request);
        return new NoteResource($note);
    }

    public function show(Note $note)
    {
        return new NoteResource($note);
    }

    public function update(NoteRequest $request, Note $note)
    {
        $note->update($request->validated());
        return new NoteResource($note);
    }

    public function destroy(Note $note)
    {

        $note->delete();
        return response()->json();
    }
}
