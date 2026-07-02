<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FireHydrantItem extends Model
{
    protected $table = 'fire_hydrant_items';
    const UPDATED_AT = null;

    protected $fillable = [
        'inspection_id', 'hydrant_number', 'name', 'location_detail',
        'hose_condition', 'nozzle_condition', 'coupling_condition',
        'wrench_condition', 'valve_condition', 'coupling_extra_condition',
        'remark', 'photo_before', 'photo_after', 'item_lat', 'item_lng'
    ];

    public function inspection()
    {
        return $this->belongsTo(FireHydrantInspection::class, 'inspection_id');
    }
}
