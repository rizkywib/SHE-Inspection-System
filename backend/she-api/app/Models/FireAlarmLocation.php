<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FireAlarmLocation extends Model
{
    protected $table = 'fire_alarm_location';
    protected $primaryKey = 'id_location';
    const CREATED_AT = 'created';
    const UPDATED_AT = null;

    protected $fillable = [
        'name',
    ];
}
