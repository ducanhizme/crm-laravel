<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user,Workspace $workspace): bool
    {
        return $workspace->hasMember($user);
    }

    public function view(User $user, Note $note): bool
    {
        return $note->workspace->hasMember($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Note $note): bool
    {
        return $note->created_by === $user->id && $note->workspace->hasMember($user);
    }

    public function delete(User $user, Note $note): bool
    {
        return $note->created_by === $user->id && $note->workspace->hasMember($user);
    }

    public function restore(User $user, Note $note): bool
    {
    }

    public function forceDelete(User $user, Note $note): bool
    {
    }
}
