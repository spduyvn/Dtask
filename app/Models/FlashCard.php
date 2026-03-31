<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'flash_card_group_id',
        'question',
        'answer',
        'image_path',
        'level',
        'last_reviewed_at',
        'next_review_at',
    ];

    protected $casts = [
        'last_reviewed_at' => 'datetime',
        'next_review_at' => 'datetime',
        'level' => 'integer',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? route('api.flash-cards.image', $this->id) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function flashCardGroup(): BelongsTo
    {
        return $this->belongsTo(FlashCardGroup::class);
    }
}
