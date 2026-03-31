<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTask extends Model
{
    public const STATUS_NOT_STARTED = 0;
    public const STATUS_IN_PROGRESS = 1;
    public const STATUS_PAUSED = 2;
    public const STATUS_COMPLETED = 3;

    protected $fillable = [
        'user_id',
        'project_id',
        'title',
        'description',
        'start_date',
        'due_date',
        'status',
        'start_at',
        'due_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'start_at' => 'datetime',
        'due_at' => 'datetime',
        'status' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
