<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Http\Requests\WorkspaceRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use HasApiTokens, HasFactory, Notifiable;

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

}
