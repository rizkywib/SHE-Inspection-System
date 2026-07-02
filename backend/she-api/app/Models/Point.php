<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    protected $table = 'point';
    const UPDATED_AT = null;

    protected $fillable = [
        'name_point', 'lat', 'lng', 'status', 'ket1', 'ket2', 'qr_code', 'qr_generated_at',
    ];
}
