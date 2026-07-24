<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermitMainArea extends Model
{
    protected $fillable = ['name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function subAreas(): HasMany
    {
        return $this->hasMany(PermitSubArea::class, 'main_area_id');
    }
}
