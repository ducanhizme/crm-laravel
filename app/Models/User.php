<?php

namespace App\Models;

use App\Events\InvitationSentEvent;
use App\Events\RegisterEvent;
use App\Http\Requests\WorkspaceRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;
    use HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'google_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function joinedWorkspace(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class);
    }

    public function createdWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class,'created_by');
    }

    public function createWorkspace($request){
        $workspace = $this->createdWorkspaces()->create($request);
        $this->joinWorkspace($workspace);
        return $workspace;
    }

    public function joinWorkspace(Workspace $workspace): void
    {
        $this->joinedWorkspace()->attach($workspace);
    }

    public static function inviteToWorkspace(Request $request): Invitation
    {
        $workspace = $request->current_workspace;
        $invitation=  $workspace->invitations()->create([
            'email' => $request->email,
            'token' => \Str::random(32),
            'expires_at' => now()->addHour(),
        ]) ;
        InvitationSentEvent::dispatch($invitation);
        return $invitation;
    }

    public function hasJoinedWorkspace(string $workspaceId): bool
    {
        return $this->joinedWorkspace()->where('workspace_id', $workspaceId)->exists();
    }


    public function leaveWorkspace(string $workspaceId): int
    {
        return $this->joinedWorkspace()->detach($workspaceId);
    }

    public function tasks(): HasMany{
      return $this->hasMany(Task::class,'created_by');
    }

    public function assignedTasks(): HasMany
    {
        return $this->HasMany(Task::class,'assignee');
    }

    public function createTask($request){
        return $this->tasks()->create($request);
    }


    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
}
