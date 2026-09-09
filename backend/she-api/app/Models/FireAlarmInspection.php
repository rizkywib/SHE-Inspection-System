<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FireAlarmInspection extends Model
{
    protected $table = 'fire_alarm_inspections';

    protected $fillable = [
        'reference_no', 'location_id', 'area_id', 'qr_code_id',
        'inspection_date', 'inspector_id', 'assigned_to',
        'checkin_lat', 'checkin_lng', 'checked_in_at',
        'signed_at', 'notes', 'status'
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'checked_in_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(FireAlarmItem::class, 'inspection_id');
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function location()
    {
        return $this->belongsTo(FireAlarmLocation::class, 'location_id', 'id_location');
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}