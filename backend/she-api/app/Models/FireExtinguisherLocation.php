<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FireExtinguisherLocation extends Model
{
    protected $table = 'fire_extinguisher_location';
    protected $primaryKey = 'id_location';
    const CREATED_AT = 'created';
    const UPDATED_AT = null;

    protected $fillable = [
        'name',
    ];
}
