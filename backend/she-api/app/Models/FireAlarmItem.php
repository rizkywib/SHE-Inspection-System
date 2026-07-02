<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FireAlarmItem extends Model
{
    protected $table = 'fire_alarm_items';
    const UPDATED_AT = null;

    protected $fillable = [
        'inspection_id', 'name', 'alarm_number', 'type', 'location_detail',
        'condition_good', 'correction_needed', 'remark',
        'photo_before', 'photo_after', 'item_lat', 'item_lng'
    ];
}
