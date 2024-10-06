<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user,Workspace $workspace): bool
    {
        return $user->id === $workspace->created_by;
    }

    public function view(User $user, Company $company,Workspace $workspace): bool
    {
        return $user->id === $workspace->created_by && $company->workspace_id === $workspace->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Company $company,Workspace $workspace): bool
    {
        return $user->id === $workspace->created_by && $company->workspace_id === $workspace->id;
    }

    public function delete(User $user, Company $company,Workspace $workspace): bool
    {
        return $user->id === $workspace->created_by && $company->workspace_id === $workspace->id;
    }

    public function restore(User $user, Company $company): bool
    {
    }

    public function forceDelete(User $user, Company $company): bool
    {
    }
}
