<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalReport extends Model
{
    protected $table = 'medical_reports';

    protected $fillable = [
        'incident_id', 'report_date', 'case_no', 'patrol_no',
        'patient_name', 'department_id', 'section_id',
        'incident_date', 'incident_time', 'immediate_supervisor',
        'facility_supervisor', 'nature_of_injury', 'treatment_given',
        'recommendation', 'estimated_lost_days', 'restricted_work_days',
        'injury_classification', 'medic_name', 'investigator_name',
        'safety_supervisor', 'file_name', 'file_type', 'file_size', 'status'
    ];

    protected $casts = [
        'report_date' => 'date',
        'incident_date' => 'date',
        'incident_time' => 'string',
    ];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}