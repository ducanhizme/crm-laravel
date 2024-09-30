<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'name',
        'created_by',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class );
    }

    public function hasMember(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    public function currentUser(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'current_workspace_id');
    }

    public function invitations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function addUser(User $user): void
    {
        $this->users()->attach($user);
    }
}
