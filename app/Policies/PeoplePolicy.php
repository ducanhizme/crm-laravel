<?php

namespace App\Policies;

use App\Models\People;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\HandlesAuthorization;

class PeoplePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Workspace $workspace): bool
    {
        return $workspace->hasMember($user);
    }

    public function view(User $user, People $people,Workspace $workspace): bool
    {
        return $workspace->hasMember($user) && $people->workspace_id == $workspace->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, People $people,Workspace $workspace): bool
    {
        return $workspace->hasMember($user) && $people->workspace_id == $workspace->id;
    }

    public function delete(User $user, People $people,Workspace $workspace): bool
    {
        return $workspace->hasMember($user) && $people->workspace_id == $workspace->id;
    }

    public function restore(User $user, People $people): bool
    {
    }

    public function forceDelete(User $user, People $people): bool
    {
    }
}
