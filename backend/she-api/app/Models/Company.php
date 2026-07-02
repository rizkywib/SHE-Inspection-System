<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'companies';
    protected $fillable = ['name', 'address', 'province', 'city', 'ceo_name', 'ceo_email', 'ceo_phone', 'group_code'];

    public function branches() { return $this->hasMany(Branch::class); }
    public function locations() { return $this->hasMany(Location::class); }
    public function users() { return $this->hasMany(User::class); }
}