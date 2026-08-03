<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EsEwArea extends Model
{
    protected $table = 'es_ew_areas';

    protected $fillable = [
        'name',
    ];

    public function inspections()
    {
        return $this->hasMany(EsEwInspection::class, 'area_id');
    }
}
