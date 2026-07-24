<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SafetyTalkSpeaker extends Model
{
    protected $fillable = ['name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function trainings(): HasMany
    {
        return $this->hasMany(SafetyTalkTraining::class, 'speaker_id');
    }
}
