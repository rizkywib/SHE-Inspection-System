<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $table = 'branches';

    protected $fillable = [
        'company_id', 'name', 'address', 'province', 'city',
        'head_name', 'email', 'group_code'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function divisions()
    {
        return $this->hasMany(Division::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }
}