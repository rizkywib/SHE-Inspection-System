<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermitSubArea extends Model
{
    protected $fillable = ['main_area_id', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function mainArea(): BelongsTo
    {
        return $this->belongsTo(PermitMainArea::class, 'main_area_id');
    }

    public function permitInspections(): HasMany
    {
        return $this->hasMany(SafeWorkPermitInspection::class, 'sub_area_id');
    }
}
