<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FireExtinguisherItem extends Model
{
    protected $table = 'fire_extinguisher_items';
    const UPDATED_AT = null;

    protected $fillable = [
        'inspection_id', 'name', 'type', 'location_detail',
        'pressure_condition', 'seal_condition', 'nozzle_condition',
        'remark', 'expiry_date', 'photo_before', 'photo_after',
        'item_lat', 'item_lng'
    ];
}
