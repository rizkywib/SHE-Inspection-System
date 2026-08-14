<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupervisionArea extends Model
{
    protected $fillable = ['code', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function permitInspections(): HasMany
    {
        return $this->hasMany(SafeWorkPermitInspection::class, 'supervision_area_id');
    }
}
