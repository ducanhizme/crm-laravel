<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task,Workspace $workspace): bool
    {
        return $workspace->hasMember($user) && $task->workspace_id == $workspace->id;
    }

    public function create(User $user): bool
    {
    }

    public function update(User $user, Task $task,Workspace $workspace): bool
    {
        return $workspace->hasMember($user) && $task->workspace_id == $workspace->id;
    }

    public function delete(User $user, Task $task,Workspace $workspace): bool
    {
        return $workspace->hasMember($user) && $task->workspace_id == $workspace->id;
    }

}
