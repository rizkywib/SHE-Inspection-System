<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentInvestigation extends Model
{
    protected $table = 'incident_investigations';

    protected $fillable = [
        'incident_id', 'investigation_no', 'investigation_date',
        'investigator_id', 'findings', 'root_cause', 'recommendations',
        'file_attachment', 'status'
    ];

    protected $casts = [
        'investigation_date' => 'date',
    ];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }

    public function investigator()
    {
        return $this->belongsTo(User::class, 'investigator_id');
    }
}