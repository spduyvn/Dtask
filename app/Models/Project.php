<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'color',
        'owner_id',
        'start_date',
        'end_date',
        'project_type_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'project_type_id' => 'integer',
    ];

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(UserTask::class);
    }

    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }


}
