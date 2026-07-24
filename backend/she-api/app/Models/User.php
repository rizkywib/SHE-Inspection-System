<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Division;
use Illuminate\Support\Facades\DB;

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
        'signature_path',
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

    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'super_admin') {
            return true;
        }

        [$module, $action] = $this->parsePermission($permission);

        return DB::table('user_group_members')
            ->join('user_groups', 'user_groups.id', '=', 'user_group_members.group_id')
            ->join('permissions', 'permissions.group_id', '=', 'user_group_members.group_id')
            ->where('user_group_members.user_id', $this->id)
            ->where('user_groups.is_active', true)
            ->where('permissions.module', $module)
            ->where($action, true)
            ->exists();
    }

    public function permissionNames(): array
    {
        if ($this->role === 'super_admin') {
            return [
                'safe-work-permit-inspection.view',
                'safe-work-permit-inspection.create',
                'safe-work-permit-inspection.update',
                'safe-work-permit-inspection.delete',
                'safety-talk-training.view',
                'safety-talk-training.create',
                'safety-talk-training.update',
                'safety-talk-training.delete',
            ];
        }

        $rows = DB::table('user_group_members')
            ->join('user_groups', 'user_groups.id', '=', 'user_group_members.group_id')
            ->join('permissions', 'permissions.group_id', '=', 'user_group_members.group_id')
            ->where('user_group_members.user_id', $this->id)
            ->where('user_groups.is_active', true)
            ->get(['permissions.module', 'can_view', 'can_create', 'can_edit', 'can_delete']);

        return $rows->flatMap(function ($row) {
            return collect([
                'view' => 'can_view',
                'create' => 'can_create',
                'update' => 'can_edit',
                'delete' => 'can_delete',
            ])->filter(fn (string $column) => (bool) $row->{$column})
                ->keys()
                ->map(fn (string $action) => "{$row->module}.{$action}");
        })
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function parsePermission(string $permission): array
    {
        $position = strrpos($permission, '.');
        $module = $position === false ? $permission : substr($permission, 0, $position);
        $action = $position === false ? 'view' : substr($permission, $position + 1);
        $columns = [
            'view' => 'can_view',
            'create' => 'can_create',
            'update' => 'can_edit',
            'delete' => 'can_delete',
        ];

        return [$module, $columns[$action] ?? 'can_view'];
    }
}
