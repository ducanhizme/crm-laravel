<?php

namespace App\Policies;

use App\Models\Status;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\HandlesAuthorization;

class StatusPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user,Workspace $workspace): bool
    {
        return  $workspace->hasMember($user);
    }

    public function view(User $user, Status $status,Workspace $workspace): bool
    {
        return $workspace->hasMember($user) && $status->workspace_id == $workspace->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Status $status,Workspace $workspace): bool
    {
        return $workspace->hasMember($user) && $status->workspace_id == $workspace->id;
    }

    public function delete(User $user, Status $status,Workspace $workspace): bool
    {
        return $status->workspace->hasMember($user) && $status->workspace_id == $workspace->id;
    }

    public function restore(User $user, Status $status): bool
    {
    }

    public function forceDelete(User $user, Status $status): bool
    {
    }
}
