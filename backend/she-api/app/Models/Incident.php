<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $table = 'incidents';

    protected $fillable = [
        'reference_no', 'reporter_id', 'incident_type_id', 'incident_level_id',
        'location_id', 'area_id', 'department_id', 'section_id',
        'incident_date', 'incident_time', 'description', 'root_cause',
        'immediate_action', 'recommendation', 'corrective_action', 'review_notes',
        'latitude', 'longitude', 'status', 'is_medical'
    ];

    protected $casts = [
        'incident_date' => 'date',
        'is_medical' => 'boolean',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function incidentType()
    {
        return $this->belongsTo(IncidentType::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function images()
    {
        return $this->hasMany(IncidentImage::class);
    }

    public function investigations()
    {
        return $this->hasMany(IncidentInvestigation::class);
    }

    public function medicalReports()
    {
        return $this->hasMany(MedicalReport::class);
    }
}