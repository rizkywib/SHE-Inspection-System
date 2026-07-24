<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafeWorkPermitInspection extends Model
{
    protected $fillable = [
        'permit_date',
        'inspector_id',
        'permit_number',
        'permit_type_id',
        'supervision_area_id',
        'main_area_id',
        'sub_area_id',
        'section_equipment',
        'job_performance',
        'authorized_craftman',
        'authorized_facility',
        'contractor_name',
        'work_description',
        'permit_findings',
    ];

    protected $casts = [
        'permit_date' => 'date:Y-m-d',
    ];

    protected $appends = ['finding_status'];

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(PermitInspector::class, 'inspector_id');
    }

    public function permitType(): BelongsTo
    {
        return $this->belongsTo(PermitType::class, 'permit_type_id');
    }

    public function supervisionArea(): BelongsTo
    {
        return $this->belongsTo(SupervisionArea::class, 'supervision_area_id');
    }

    public function mainArea(): BelongsTo
    {
        return $this->belongsTo(PermitMainArea::class, 'main_area_id');
    }

    public function subArea(): BelongsTo
    {
        return $this->belongsTo(PermitSubArea::class, 'sub_area_id');
    }

    public function getFindingStatusAttribute(): string
    {
        return filled($this->permit_findings) ? 'Ada Temuan' : 'Tidak Ada Temuan';
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!filled($search)) {
            return $query;
        }

        $search = trim($search);

        return $query->where(function (Builder $query) use ($search) {
            $query->where('permit_number', 'like', "%{$search}%")
                ->orWhere('section_equipment', 'like', "%{$search}%")
                ->orWhere('contractor_name', 'like', "%{$search}%")
                ->orWhereHas('inspector', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
        });
    }
}
