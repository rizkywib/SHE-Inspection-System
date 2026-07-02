<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FireHydrantLocation extends Model
{
    protected $table = 'fire_hydrant_location';
    protected $primaryKey = 'id_location';
    const CREATED_AT = 'created';
    const UPDATED_AT = null;

    protected $fillable = [
        'name',
    ];
}
