<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'title',
        'created_by',
        'due_date',
        'assignee',
        'body',
        'status_id',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class,'workspace_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
        ];
    }

    public function people(): HasMany
    {
        return $this->hasMany(People::class);
    }
}
