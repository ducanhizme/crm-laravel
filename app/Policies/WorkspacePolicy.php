<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use App\Traits\HasApiResponse;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkspacePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Workspace $workspace): bool
    {
        return $user->id === $workspace->created_by || $workspace->hasMember($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return  $user->id === $workspace->created_by;
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return  $user->id === $workspace->created_by;
    }

    public function removeUser(User $user, Workspace $workspace,$onRemoveUserId): bool
    {
        return  $user->id === $workspace->created_by && $workspace->hasMember(User::find($onRemoveUserId));
    }

}

