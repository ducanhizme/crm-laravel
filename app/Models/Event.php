<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder; // Required for scope

class Event extends Model
{
    use HasFactory;

    // ... (existing properties and methods)

    /**
     * Scope a query to only include events starting between two dates.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $dates Two-element array with start and end datetimes for the range
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStartsBetween(Builder $query, array $dates): Builder
    {
        if (count($dates) == 2) {
            return $query->whereBetween('start_time', [$dates[0], $dates[1]]);
        }
        return $query;
    }

    /**
     * Scope a query to only include events ending between two dates.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $dates Two-element array with start and end datetimes for the range
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEndsBetween(Builder $query, array $dates): Builder
    {
        if (count($dates) == 2) {
            return $query->whereBetween('end_time', [$dates[0], $dates[1]]);
        }
        return $query;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'workspace_id',
        'title',
        'description',
        'event_type',
        'start_time',
        'end_time',
        'google_calendar_event_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get the user that owns the event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the workspace that the event belongs to.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
