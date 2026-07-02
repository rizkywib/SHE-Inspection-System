<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\EsEwItem;

class EsEwInspection extends Model
{
    protected $table = 'es_ew_inspections';

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
        return $this->hasMany(EsEwItem::class, 'inspection_id');
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}