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

    protected $casts = [
        'water_flow_es' => 'boolean',
        'water_flow_ew' => 'boolean',
        'water_condition' => 'boolean',
        'actual_valve_es' => 'boolean',
        'actual_valve_ew' => 'boolean',
        'physical_condition_es' => 'boolean',
        'physical_condition_ew' => 'boolean',
        'sign_board_condition' => 'boolean',
        'housekeeping_condition' => 'boolean',
        'road_access_condition' => 'boolean',
        'sewer_condition' => 'boolean',
        'item_lat' => 'decimal:8',
        'item_lng' => 'decimal:8',
    ];

    public function inspection()
    {
        return $this->belongsTo(EsEwInspection::class, 'inspection_id');
    }
}
