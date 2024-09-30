<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvitationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {

    }

    public function view(User $user, Invitation $invitation): bool
    {
    }

    public function create(User $user): bool
    {
    }

    public function update(User $user, Invitation $invitation): bool
    {
    }

    public function delete(User $user, Invitation $invitation): bool
    {
    }

    public function restore(User $user, Invitation $invitation): bool
    {
    }

    public function forceDelete(User $user, Invitation $invitation): bool
    {
    }
}
