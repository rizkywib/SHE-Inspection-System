<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyTalkTraining extends Model
{
    public const IMPLEMENTATION_AREAS = [1, 2, 3, 4, 5, 6];

    protected $fillable = [
        'speaker_id',
        'implementation_date',
        'topic',
        'ecogreen_participants',
        'outsourcing_participants',
        'contractor_participants',
        'duration_minutes',
        'implementation_area',
        'activity_photo_path',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'implementation_date' => 'date:Y-m-d',
        'ecogreen_participants' => 'integer',
        'outsourcing_participants' => 'integer',
        'contractor_participants' => 'integer',
        'duration_minutes' => 'integer',
        'implementation_area' => 'integer',
    ];

    protected $appends = ['total_participants', 'activity_photo_url'];

    public function speaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'speaker_id');
    }

    public function legacySpeaker(): BelongsTo
    {
        return $this->belongsTo(SafetyTalkSpeaker::class, 'legacy_speaker_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getTotalParticipantsAttribute(): int
    {
        return $this->ecogreen_participants
            + $this->outsourcing_participants
            + $this->contractor_participants;
    }

    public function getActivityPhotoUrlAttribute(): ?string
    {
        return $this->activity_photo_path
            ? asset('storage/' . ltrim($this->activity_photo_path, '/'))
            : null;
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!filled($search)) {
            return $query;
        }

        $search = trim($search);

        return $query->where(function (Builder $query) use ($search) {
            $query->where('topic', 'like', "%{$search}%")
                ->orWhereHas('speaker', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                ->orWhereHas('legacySpeaker', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
        });
    }
}
