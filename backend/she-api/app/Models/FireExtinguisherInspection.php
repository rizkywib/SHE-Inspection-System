<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FireExtinguisherInspection extends Model
{
    protected $table = 'fire_extinguisher_inspections';

    protected $fillable = [
        'reference_no', 'location_id',
        'inspection_date', 'inspector_id',
        'checkin_lat', 'checkin_lng', 'checked_in_at',
        'signed_at', 'signed_by'
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'checked_in_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(FireExtinguisherItem::class, 'inspection_id');
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function signer()
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function location()
    {
        return $this->belongsTo(FireExtinguisherLocation::class, 'location_id', 'id_location');
    }
}
