<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Section;

class Department extends Model
{
    protected $table = 'departments';

    protected $fillable = [
        'branch_id', 'division_id', 'name', 'head_name', 'email', 'phone', 'group_code'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}