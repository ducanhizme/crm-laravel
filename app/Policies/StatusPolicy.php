<?php

namespace App\Policies;

use App\Models\Status;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StatusPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {

    }

    public function view(User $user, Status $status): bool
    {
    }

    public function create(User $user): bool
    {
    }

    public function update(User $user, Status $status): bool
    {
    }

    public function delete(User $user, Status $status): bool
    {
    }

    public function restore(User $user, Status $status): bool
    {
    }

    public function forceDelete(User $user, Status $status): bool
    {
    }
}
