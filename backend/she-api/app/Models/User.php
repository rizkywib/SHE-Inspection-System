<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Division;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'password_hash',
        'phone',
        'position',
        'company_id',
        'branch_id',
        'division_id',
        'department_id',
        'section_id',
        'role',
        'is_active',
        'last_login',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function fireHydrantInspections()
    {
        return $this->hasMany(FireHydrantInspection::class, 'inspector_id');
    }

    public function fireExtinguisherInspections()
    {
        return $this->hasMany(FireExtinguisherInspection::class, 'inspector_id');
    }

    public function fireAlarmInspections()
    {
        return $this->hasMany(FireAlarmInspection::class, 'inspector_id');
    }

    public function esEwInspections()
    {
        return $this->hasMany(EsEwInspection::class, 'inspector_id');
    }

    public function incidents()
    {
        return $this->hasMany(Incident::class, 'reporter_id');
    }

    public function isAdmin()
    {
        return in_array($this->role, ['super_admin', 'admin']);
    }

    public function isInspector()
    {
        return $this->role === 'inspector';
    }
}
