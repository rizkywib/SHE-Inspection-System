<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationType extends Model
{
    protected $table = 'location_types';

    protected $fillable = ['name', 'group_code'];

    public function locations()
    {
        return $this->hasMany(Location::class);
    }
}