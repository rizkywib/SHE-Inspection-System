<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EsEwItem extends Model
{
    protected $table = 'es_ew_items';
    const UPDATED_AT = null;

    protected $fillable = [
        'inspection_id', 'name', 'type', 'location_detail', 'area_detail',
        'water_flow_es', 'water_flow_ew', 'water_condition',
        'actual_valve_es', 'actual_valve_ew',
        'physical_condition_es', 'physical_condition_ew',
        'sign_board_condition', 'housekeeping_condition',
        'road_access_condition', 'sewer_condition',
        'remark', 'photo_before', 'photo_after',
        'item_lat', 'item_lng'
    ];

    public function inspection()
    {
        return $this->belongsTo(EsEwInspection::class, 'inspection_id');
    }
}